<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Models\Assessment;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\Session;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentScoreService;
use App\Services\AssessmentService;
use App\Services\AttendanceScoringService;
use App\Services\AttendanceService;
use App\Services\CoursePersonService;
use App\Services\ForumDiscussionScoringService;
use App\Services\ForumService;
use App\Services\ForumThreadService;
use App\Services\GradebookScoringService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

trait HasGradebookIndexDevTools
{
    public bool $isLocalEnv = false;

    public function randomizeScores(
        AssessmentService $assessmentService,
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentScoreService $assessmentScoreService,
        AssessmentQuestionService $assessmentQuestionService,
        CoursePersonService $coursePersonService,
        GroupService $groupService,
        GroupMemberService $groupMemberService,
        AttendanceService $attendanceService,
        AttendanceScoringService $attendanceScoringService,
        ForumService $forumService,
        ForumThreadService $forumThreadService,
        ForumDiscussionScoringService $forumDiscussionScoringService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        abort_unless(app()->environment('local'), 403);
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $this->successMessage = null;

        $assessments = $assessmentService->forCourse($this->course->id)->load('questions');
        $students = $coursePersonService->studentsForCourse($this->course->id);

        if ($assessments->isEmpty() || $students->isEmpty()) {
            return;
        }

        $graderId = auth()->id();
        $targetPercentages = $this->assignGradeBandTargets($students);

        $hasTeamAssignment = $assessments->contains(fn (Assessment $assessment) => $assessment->type === AssessmentType::TheoryTeamAssignment);
        $groups = $hasTeamAssignment
            ? $this->ensureGroupsForCourse($students, $groupService, $groupMemberService)
            : collect();

        foreach ($assessments as $assessment) {
            match ($assessment->type) {
                AssessmentType::Attendance => $this->randomizeAttendance($assessment, $students, $targetPercentages, $graderId, $attendanceService, $attendanceScoringService),
                AssessmentType::ForumDiscussion => $this->randomizeForumDiscussion($assessment, $students, $targetPercentages, $forumService, $forumThreadService, $forumDiscussionScoringService),
                AssessmentType::TheoryTeamAssignment => $this->randomizeTeamAssignment($assessment, $groups, $targetPercentages, $assessmentAttemptService, $assessmentScoreService, $assessmentQuestionService, $graderId),
                default => $this->randomizeIndividualAssessment($assessment, $students, $targetPercentages, $assessmentAttemptService, $assessmentScoreService, $assessmentQuestionService, $graderId),
            };
        }

        foreach ($students as $coursePerson) {
            $gradebookScoringService->recomputeForUser($this->course, $coursePerson->user_id);
        }

        $this->successMessage = __('Randomized gradebook scores for :count student(s).', ['count' => $students->count()]);
    }

    #[On('delete-confirmed')]
    public function resetScores(
        AssessmentService $assessmentService,
        AssessmentAttemptService $assessmentAttemptService,
        AttendanceService $attendanceService,
        ForumService $forumService,
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        abort_unless(app()->environment('local'), 403);
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $this->successMessage = null;

        foreach ($assessmentService->forCourse($this->course->id) as $assessment) {
            foreach ($assessmentAttemptService->get(['assessment_id' => $assessment->id]) as $attempt) {
                $assessmentAttemptService->delete($attempt->id);
            }
        }

        $attendanceService->deleteForCourse($this->course->id);

        foreach ($forumService->get(['course_id' => $this->course->id]) as $forum) {
            $forumService->delete($forum->id);
        }

        $students = $coursePersonService->studentsForCourse($this->course->id);

        foreach ($students as $coursePerson) {
            $gradebookScoringService->recomputeForUser($this->course, $coursePerson->user_id);
        }

        $this->successMessage = __('Reset all gradebook scores for this course.');
    }

    /**
     * Assigns each student a target final percentage cycling through the
     * A-E letter grade bands (see BuildsGradebookViewData::letterGrade), so
     * a randomize run always produces at least one student per grade
     * (when there are at least 5 students). Every gradable component for a
     * student is then driven toward this same target percentage, since the
     * final grade is a weighted average of those components' percentages.
     *
     * @param  Collection<int, CoursePerson>  $students
     * @return array<string, int> user_id => target percentage (0-100)
     */
    private function assignGradeBandTargets(Collection $students): array
    {
        $bands = [
            [90, 100], // A
            [80, 89],  // B
            [70, 79],  // C
            [60, 69],  // D
            [30, 59],  // E
        ];

        $targets = [];

        foreach ($students->shuffle()->values() as $index => $coursePerson) {
            [$min, $max] = $bands[$index % count($bands)];
            $targets[$coursePerson->user_id] = random_int($min, $max);
        }

        return $targets;
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     * @param  array<string, int>  $targetPercentages
     */
    private function randomizeAttendance(Assessment $assessment, Collection $students, array $targetPercentages, ?string $graderId, AttendanceService $attendanceService, AttendanceScoringService $attendanceScoringService): void
    {
        $sessions = $attendanceScoringService->sessionsInScope($assessment)->values();

        if ($sessions->isEmpty()) {
            return;
        }

        foreach ($students as $coursePerson) {
            $percentage = $targetPercentages[$coursePerson->user_id] ?? random_int(40, 100);
            $attendCount = (int) round($sessions->count() * ($percentage / 100));
            $presentSessionIds = $sessions->shuffle()->take($attendCount)->pluck('id');

            foreach ($sessions as $session) {
                $data = [
                    'session_id' => $session->id,
                    'user_id' => $coursePerson->user_id,
                    'status' => $presentSessionIds->contains($session->id) ? AttendanceStatus::Present : AttendanceStatus::Absent,
                    'recorded_by' => $graderId,
                    'recorded_at' => now(),
                ];

                $existing = $attendanceService->findBySessionAndUser($session->id, $coursePerson->user_id);

                if ($existing) {
                    $attendanceService->update($existing->id, $data);
                } else {
                    $attendanceService->create($data);
                }
            }

            $attendanceScoringService->recomputeForUser($assessment, $coursePerson->user_id);
        }
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     * @param  array<string, int>  $targetPercentages
     */
    private function randomizeForumDiscussion(Assessment $assessment, Collection $students, array $targetPercentages, ForumService $forumService, ForumThreadService $forumThreadService, ForumDiscussionScoringService $forumDiscussionScoringService): void
    {
        $sessions = $forumDiscussionScoringService->sessionsInScope($assessment)->values();

        if ($sessions->isEmpty()) {
            return;
        }

        foreach ($students as $coursePerson) {
            $percentage = $targetPercentages[$coursePerson->user_id] ?? random_int(40, 100);
            $metCount = (int) round($sessions->count() * ($percentage / 100));
            $metSessionIds = $sessions->shuffle()->take($metCount)->pluck('id');

            foreach ($sessions as $session) {
                $meetsRequirement = $metSessionIds->contains($session->id);
                $this->randomizeForumPostsForSession($session, $coursePerson->user_id, $meetsRequirement, $assessment, $forumService, $forumThreadService, $forumDiscussionScoringService);
            }

            $forumDiscussionScoringService->recomputeForUser($assessment, $coursePerson->user_id);
        }
    }

    private function randomizeForumPostsForSession(Session $session, string $userId, bool $meetsRequirement, Assessment $assessment, ForumService $forumService, ForumThreadService $forumThreadService, ForumDiscussionScoringService $forumDiscussionScoringService): void
    {
        $forum = $forumService->findOrCreateForSession($session->id, $assessment->course_id);
        $required = $forumDiscussionScoringService->requiredForumPosts($session);
        $target = $meetsRequirement ? $required : random_int(0, max(0, $required - 1));

        foreach ($forumThreadService->forUserInSession($userId, $session->id) as $existingThread) {
            $forumThreadService->delete($existingThread->id);
        }

        for ($i = 0; $i < $target; $i++) {
            $forumThreadService->create([
                'forum_id' => $forum->id,
                'user_id' => $userId,
                'title' => 'Randomly generated discussion #'.($i + 1),
                'description' => 'Randomly generated forum post for development purposes.',
            ]);
        }
    }

    /**
     * Team Assignment scoring is keyed by Group, not by student, so without
     * any Group set up for the course there is nothing to grade and the
     * gradebook row stays empty. For a dev-only randomizer that's not
     * useful, so when the course has no groups yet, split enrolled students
     * into groups of up to 4 before grading.
     *
     * @param  Collection<int, CoursePerson>  $students
     * @return Collection<int, Group>
     */
    private function ensureGroupsForCourse(Collection $students, GroupService $groupService, GroupMemberService $groupMemberService): Collection
    {
        $groups = $groupService->forCourse($this->course->id);

        if ($groups->isNotEmpty()) {
            return $groups;
        }

        $creatorId = auth()->id();

        foreach ($students->values()->chunk(4) as $index => $chunk) {
            $group = $groupService->create([
                'course_id' => $this->course->id,
                'name' => 'Randomly generated group '.($index + 1),
                'created_by' => $creatorId,
            ]);

            foreach ($chunk as $coursePerson) {
                $groupMemberService->create([
                    'group_id' => $group->id,
                    'user_id' => $coursePerson->user_id,
                    'joined_at' => now(),
                ]);
            }
        }

        return $groupService->forCourse($this->course->id);
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @param  array<string, int>  $targetPercentages
     */
    private function randomizeTeamAssignment(Assessment $assessment, Collection $groups, array $targetPercentages, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionService $assessmentQuestionService, ?string $graderId): void
    {
        $totalPoints = $this->ensureGradableQuestionPoints($assessment, $assessmentQuestionService);

        if ($totalPoints <= 0.0) {
            return;
        }

        foreach ($groups as $group) {
            $percentage = $this->groupTargetPercentage($group, $targetPercentages);
            $this->gradeAttempt($assessment, $totalPoints, $percentage, $assessmentAttemptService, $assessmentScoreService, $graderId, groupId: $group->id, userId: null, submittedBy: $this->firstMemberId($group));
        }
    }

    /**
     * @param  array<string, int>  $targetPercentages
     */
    private function groupTargetPercentage(Group $group, array $targetPercentages): int
    {
        $memberPercentages = $group->members
            ->map(fn ($member) => $targetPercentages[$member->user_id] ?? null)
            ->filter()
            ->values();

        if ($memberPercentages->isEmpty()) {
            return random_int(40, 100);
        }

        return (int) round($memberPercentages->avg());
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     * @param  array<string, int>  $targetPercentages
     */
    private function randomizeIndividualAssessment(Assessment $assessment, Collection $students, array $targetPercentages, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionService $assessmentQuestionService, ?string $graderId): void
    {
        $totalPoints = $this->ensureGradableQuestionPoints($assessment, $assessmentQuestionService);

        if ($totalPoints <= 0.0) {
            return;
        }

        foreach ($students as $coursePerson) {
            $percentage = $targetPercentages[$coursePerson->user_id] ?? random_int(40, 100);
            $this->gradeAttempt($assessment, $totalPoints, $percentage, $assessmentAttemptService, $assessmentScoreService, $graderId, groupId: null, userId: $coursePerson->user_id, submittedBy: $coursePerson->user_id);
        }
    }

    private function firstMemberId(Group $group): ?string
    {
        return $group->members->first()?->user_id;
    }

    /**
     * Personal/Team Assignment, Quiz and Final Exam scores are computed as
     * (AssessmentScore.score / sum of AssessmentQuestion.points), so an
     * assessment with no questions yet (common for a freshly created
     * Personal/Team Assignment, since those are graded free-form rather
     * than answered question-by-question) can never produce a percentage
     * and always shows "-" in the gradebook, even after grading. For the
     * dev-only randomizer, auto-create a single grading criterion so the
     * assessment becomes gradable.
     */
    private function ensureGradableQuestionPoints(Assessment $assessment, AssessmentQuestionService $assessmentQuestionService): float
    {
        $totalPoints = (float) $assessment->questions->sum('points');

        if ($totalPoints > 0.0 || $assessment->questions->isNotEmpty()) {
            return $totalPoints;
        }

        $question = $assessmentQuestionService->create([
            'assessment_id' => $assessment->id,
            'description' => 'Randomly generated grading criterion for development purposes.',
            'points' => 100,
            'question_type' => AssessmentQuestionType::Essay,
            'order' => 1,
        ]);

        $assessment->setRelation('questions', collect([$question]));

        return (float) $question->points;
    }

    private function gradeAttempt(
        Assessment $assessment,
        float $totalPoints,
        int $percentage,
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentScoreService $assessmentScoreService,
        ?string $graderId,
        ?string $groupId,
        ?string $userId,
        ?string $submittedBy,
    ): void {
        $attempt = $assessmentAttemptService->get([
            'assessment_id' => $assessment->id,
            'user_id' => $userId,
            'group_id' => $groupId,
        ])->first();

        if (! $attempt) {
            $attempt = $assessmentAttemptService->create([
                'assessment_id' => $assessment->id,
                'user_id' => $userId,
                'group_id' => $groupId,
                'submitted_by' => $submittedBy,
                'attempt_number' => 1,
                'started_at' => now()->subMinutes(random_int(10, 120)),
                'submitted_at' => now(),
            ]);
        }

        $score = round($totalPoints * ($percentage / 100), 2);

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $data = [
            'assessment_attempt_id' => $attempt->id,
            'score' => $score,
            'graded_by' => $graderId,
            'graded_at' => now(),
            'feedback' => 'Randomly generated score for development purposes.',
        ];

        if ($existingScore) {
            $assessmentScoreService->update($existingScore->id, $data);
        } else {
            $assessmentScoreService->create($data);
        }
    }
}
