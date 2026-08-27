<?php

namespace App\Services;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Rolls up all of a course's Assessments (across every AssessmentType) into
 * per-type and final Gradebook scores for a student.
 *
 * Percentage sourcing per type:
 * - Attendance / Forum Discussion: derived live via
 *   AttendanceScoringService::computeForUser() / ForumDiscussionScoringService::computeForUser(),
 *   same as those assessments' own detail screens.
 * - Personal/Team Assignment, Quiz, Final Exam: graded AssessmentScore.score
 *   (a raw points sum, not a percentage) divided by the assessment's total
 *   question points. Ungraded assessments are excluded from both the
 *   numerator and denominator of their type's weighted average, not treated
 *   as zero.
 *
 * A course can have multiple Assessments of the same type (only Attendance
 * and Forum Discussion are auto-provisioned singletons), so a type's weight
 * is the sum of its Assessments' weights, and its score is the weight-
 * weighted average of each contributing Assessment's percentage.
 */
class GradebookScoringService
{
    public function __construct(
        private AssessmentService $assessmentService,
        private AssessmentAttemptService $assessmentAttemptService,
        private AssessmentScoreService $assessmentScoreService,
        private GroupMemberService $groupMemberService,
        private AttendanceScoringService $attendanceScoringService,
        private AttendanceDerivationService $attendanceDerivationService,
        private ForumDiscussionScoringService $forumDiscussionScoringService,
        private GradebookEntryService $gradebookEntryService,
        private GradebookSessionEntryService $gradebookSessionEntryService,
    ) {}

    /**
     * @return array{final: array{score: ?float, last_updated_at: ?Carbon}, types: array<int, array{type: AssessmentType, weight: float, score: ?float, last_updated_at: ?Carbon, sessions: array<int, array{session: Session, weight: float, score: float}>}>}
     */
    public function computeForUser(Course $course, string $userId): array
    {
        $assessmentsByType = $this->assessmentService->forCourse($course->id)->groupBy(
            fn (Assessment $assessment) => $assessment->type->value
        );

        $types = [];

        foreach (AssessmentType::cases() as $type) {
            $typeAssessments = $assessmentsByType->get($type->value, collect());

            if ($typeAssessments->isEmpty()) {
                continue;
            }

            $weight = (float) $typeAssessments->sum('weight');

            $contributions = $typeAssessments
                ->map(fn (Assessment $assessment) => $this->percentageForAssessment($assessment, $type, $userId))
                ->filter()
                ->values();

            if ($contributions->isEmpty()) {
                $types[] = [
                    'type' => $type,
                    'weight' => $weight,
                    'score' => null,
                    'last_updated_at' => null,
                    'sessions' => [],
                ];

                continue;
            }

            $weightSum = (float) $contributions->sum('weight');
            $score = $weightSum > 0.0
                ? round($contributions->sum(fn (array $c) => $c['percentage'] * $c['weight']) / $weightSum, 2)
                : null;

            $sessions = in_array($type, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true)
                ? $this->sessionBreakdown($typeAssessments, $type, $userId, $weight)
                : [];

            $types[] = [
                'type' => $type,
                'weight' => $weight,
                'score' => $score,
                'last_updated_at' => $contributions->pluck('last_updated_at')->filter()->max(),
                'sessions' => $sessions,
            ];
        }

        $contributingTypes = collect($types)->filter(fn (array $t) => $t['score'] !== null);

        $finalScore = $contributingTypes->isNotEmpty()
            ? round($contributingTypes->sum(fn (array $t) => ($t['weight'] / 100) * $t['score']), 2)
            : null;

        return [
            'final' => [
                'score' => $finalScore,
                'last_updated_at' => $contributingTypes->pluck('last_updated_at')->filter()->max(),
            ],
            'types' => $types,
        ];
    }

    /**
     * Lean lookup for a single type's session breakdown, used by the
     * accordion's lazy-loaded expand endpoint so it doesn't have to pay for
     * a full computeForUser() across every AssessmentType.
     *
     * @return array<int, array{session: Session, weight: float, score: float}>
     */
    public function sessionBreakdownForType(Course $course, string $userId, AssessmentType $type): array
    {
        if (! in_array($type, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true)) {
            return [];
        }

        $typeAssessments = $this->assessmentService->forCourse($course->id)
            ->filter(fn (Assessment $assessment) => $assessment->type === $type)
            ->values();

        if ($typeAssessments->isEmpty()) {
            return [];
        }

        $weight = (float) $typeAssessments->sum('weight');

        return $this->sessionBreakdown($typeAssessments, $type, $userId, $weight);
    }

    /**
     * Recomputes and persists the GradebookEntry/GradebookSessionEntry rows
     * for a user, called after any grading action changes an AssessmentScore.
     */
    public function recomputeForUser(Course $course, string $userId): void
    {
        $computed = $this->computeForUser($course, $userId);

        foreach ($computed['types'] as $typeRow) {
            $existingEntry = $this->gradebookEntryService->get([
                'course_id' => $course->id,
                'user_id' => $userId,
                'assessment_type' => $typeRow['type']->value,
            ])->first();

            $entryData = [
                'course_id' => $course->id,
                'user_id' => $userId,
                'assessment_type' => $typeRow['type'],
                'weight' => $typeRow['weight'],
                'score' => $typeRow['score'],
                'last_updated_at' => $typeRow['last_updated_at'],
            ];

            $entry = $existingEntry
                ? $this->gradebookEntryService->update($existingEntry->id, $entryData)
                : $this->gradebookEntryService->create($entryData);

            $existingSessionEntries = $this->gradebookSessionEntryService->forGradebookEntry($entry->id);
            $currentSessionIds = collect($typeRow['sessions'])->map(fn (array $s) => $s['session']->id);

            foreach ($typeRow['sessions'] as $sessionRow) {
                $existingSessionEntry = $existingSessionEntries->firstWhere('session_id', $sessionRow['session']->id);

                $sessionData = [
                    'gradebook_entry_id' => $entry->id,
                    'session_id' => $sessionRow['session']->id,
                    'weight' => $sessionRow['weight'],
                    'score' => $sessionRow['score'],
                ];

                if ($existingSessionEntry) {
                    $this->gradebookSessionEntryService->update($existingSessionEntry->id, $sessionData);
                } else {
                    $this->gradebookSessionEntryService->create($sessionData);
                }
            }

            foreach ($existingSessionEntries as $existingSessionEntry) {
                if (! $currentSessionIds->contains($existingSessionEntry->session_id)) {
                    $this->gradebookSessionEntryService->delete($existingSessionEntry->id);
                }
            }
        }
    }

    /**
     * @return ?array{percentage: float, weight: float, last_updated_at: ?Carbon}
     */
    private function percentageForAssessment(Assessment $assessment, AssessmentType $type, string $userId): ?array
    {
        if (in_array($type, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true)) {
            $computed = $type === AssessmentType::Attendance
                ? $this->attendanceScoringService->computeForUser($assessment, $userId)
                : $this->forumDiscussionScoringService->computeForUser($assessment, $userId);

            $score = $this->gradedScoreForUser($assessment, $type, $userId);

            return [
                'percentage' => $computed['percentage'],
                'weight' => (float) $assessment->weight,
                'last_updated_at' => $score === null ? null : ($score->graded_at ?? $score->updated_at),
            ];
        }

        $score = $this->gradedScoreForUser($assessment, $type, $userId);

        if (! $score) {
            return null;
        }

        $totalPoints = (float) $assessment->questions->sum('points');

        if ($totalPoints <= 0.0) {
            return null;
        }

        return [
            'percentage' => round(($score->score / $totalPoints) * 100, 2),
            'weight' => (float) $assessment->weight,
            'last_updated_at' => $score->graded_at,
        ];
    }

    private function gradedScoreForUser(Assessment $assessment, AssessmentType $type, string $userId): ?AssessmentScore
    {
        $attempts = $type === AssessmentType::TheoryTeamAssignment
            ? $this->attemptsForTeamMember($assessment, $userId)
            : $this->assessmentAttemptService->forAssessmentAndUser($assessment->id, $userId);

        foreach ($attempts->reverse() as $attempt) {
            $score = $this->assessmentScoreService->findByAttempt($attempt->id);

            if ($score) {
                return $score;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, AssessmentAttempt>
     */
    private function attemptsForTeamMember(Assessment $assessment, string $userId): Collection
    {
        $member = $this->groupMemberService->get(['user_id' => $userId])
            ->first(fn ($m) => $m->group->course_id === $assessment->course_id);

        if (! $member) {
            return collect();
        }

        return $this->assessmentAttemptService->forAssessmentAndGroup($assessment->id, $member->group_id);
    }

    /**
     * @return array<int, array{session: Session, weight: float, score: float}>
     */
    private function sessionBreakdown(Collection $typeAssessments, AssessmentType $type, string $userId, float $typeWeight): array
    {
        $sessions = collect();

        foreach ($typeAssessments as $assessment) {
            $inScope = $type === AssessmentType::Attendance
                ? $this->attendanceScoringService->sessionsInScope($assessment)
                : $this->forumDiscussionScoringService->sessionsInScope($assessment);

            $sessions = $sessions->merge($inScope);
        }

        $sessions = $sessions->unique('id')->values();

        if ($sessions->isEmpty()) {
            return [];
        }

        $sessionWeight = round($typeWeight / $sessions->count(), 2);

        return $sessions->map(function (Session $session) use ($type, $userId, $sessionWeight) {
            $met = $type === AssessmentType::Attendance
                ? $this->attendanceDerivationService->isSessionAttended($session, $userId)
                : $this->forumDiscussionScoringService->hasMetForumPostRequirement($session, $userId);

            return [
                'session' => $session,
                'weight' => $sessionWeight,
                'score' => $met ? 100.0 : 0.0,
            ];
        })->all();
    }
}
