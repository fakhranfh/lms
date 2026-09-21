<?php

namespace App\Services;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\Session;
use Illuminate\Support\Collection;

/**
 * Dev-only: drives every gradable component of a course (Attendance, Forum
 * Discussion, Personal/Team Assignment, Quiz, Final Exam) toward a random
 * per-student target score, so the Gradebook/Raport can be exercised without
 * hand-grading every assessment. Extracted from
 * HasGradebookIndexDevTools::randomizeScores() (Gradebook's single-course
 * "Randomize Scores" button) so the same logic can also drive
 * HasRaportIndexDevTools::generateRaportScores() (Raport's bulk, every-course
 * generator) without duplicating it.
 */
class GradebookRandomizerService
{
    public function __construct(
        private AssessmentService $assessmentService,
        private AssessmentAttemptService $assessmentAttemptService,
        private AssessmentScoreService $assessmentScoreService,
        private AssessmentQuestionService $assessmentQuestionService,
        private CoursePersonService $coursePersonService,
        private GroupService $groupService,
        private GroupMemberService $groupMemberService,
        private AttendanceService $attendanceService,
        private AttendanceScoringService $attendanceScoringService,
        private ForumService $forumService,
        private ForumThreadService $forumThreadService,
        private ForumDiscussionScoringService $forumDiscussionScoringService,
        private GradebookScoringService $gradebookScoringService,
    ) {}

    /**
     * @param  bool  $ensureEveryAssessmentType  When true, auto-creates a bare
     *                                           Assessment for any AssessmentType the course doesn't have
     *                                           one for yet, so every type in the Raport's report card
     *                                           gets a score instead of staying blank. Off by default so
     *                                           Gradebook's single-course "Randomize Scores" button keeps
     *                                           only grading assessments a teacher actually configured.
     * @return int the number of enrolled students randomized (0 when the
     *             course has no Assessments or no students yet)
     */
    public function randomizeForCourse(Course $course, ?string $graderId, bool $ensureEveryAssessmentType = false): int
    {
        if ($ensureEveryAssessmentType) {
            $this->ensureEveryAssessmentType($course);
        }

        $assessments = $this->assessmentService->forCourse($course->id)->load('questions');
        $students = $this->coursePersonService->studentsForCourse($course->id);

        if ($assessments->isEmpty() || $students->isEmpty()) {
            return 0;
        }

        $targetPercentages = $this->assignGradeBandTargets($students);

        $hasTeamAssignment = $assessments->contains(fn (Assessment $assessment) => $assessment->type === AssessmentType::TheoryTeamAssignment);
        $groups = $hasTeamAssignment
            ? $this->ensureGroupsForCourse($course, $students)
            : collect();

        foreach ($assessments as $assessment) {
            match ($assessment->type) {
                AssessmentType::Attendance => $this->randomizeAttendance($assessment, $students, $targetPercentages, $graderId),
                AssessmentType::ForumDiscussion => $this->randomizeForumDiscussion($assessment, $students, $targetPercentages),
                AssessmentType::TheoryTeamAssignment => $this->randomizeTeamAssignment($assessment, $groups, $targetPercentages, $graderId),
                default => $this->randomizeIndividualAssessment($assessment, $students, $targetPercentages, $graderId),
            };
        }

        foreach ($students as $coursePerson) {
            $this->gradebookScoringService->recomputeForUser($course, $coursePerson->user_id);
        }

        return $students->count();
    }

    /**
     * Creates a bare, published Assessment (no questions, no linked Quiz/
     * FinalExam record) for any AssessmentType the course has none of yet,
     * so it has something to grade below. Mirrors CourseService's own
     * auto-provisioning of Attendance/Forum Discussion, extended to the
     * remaining types for dev-only demo data.
     */
    private function ensureEveryAssessmentType(Course $course): void
    {
        $existingTypes = $this->assessmentService->forCourse($course->id)
            ->map(fn (Assessment $assessment) => $assessment->type->value)
            ->all();

        foreach (AssessmentType::cases() as $type) {
            if (in_array($type->value, $existingTypes, true)) {
                continue;
            }

            $this->assessmentService->create([
                'course_id' => $course->id,
                'session_id' => null,
                'type' => $type,
                'title' => $this->defaultAssessmentTitle($type),
                'weight' => $type->defaultWeight(),
                'assigned_to' => $type === AssessmentType::TheoryTeamAssignment ? AssessmentAssignedTo::Group : AssessmentAssignedTo::Individual,
                'start_date' => null,
                'end_date' => null,
                'status' => AssessmentStatus::Published,
            ]);
        }
    }

    private function defaultAssessmentTitle(AssessmentType $type): string
    {
        return match ($type) {
            AssessmentType::TheoryPersonalAssignment => 'Personal Assignment',
            AssessmentType::TheoryTeamAssignment => 'Team Assignment',
            AssessmentType::TheoryQuiz => 'Quiz',
            AssessmentType::TheoryFinalExam => 'Final Exam',
            AssessmentType::Attendance => 'Attendance',
            AssessmentType::ForumDiscussion => 'Forum Discussion',
        };
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
    private function randomizeAttendance(Assessment $assessment, Collection $students, array $targetPercentages, ?string $graderId): void
    {
        $sessions = $this->attendanceScoringService->sessionsInScope($assessment)->values();

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

                $existing = $this->attendanceService->findBySessionAndUser($session->id, $coursePerson->user_id);

                if ($existing) {
                    $this->attendanceService->update($existing->id, $data);
                } else {
                    $this->attendanceService->create($data);
                }
            }

            $this->attendanceScoringService->recomputeForUser($assessment, $coursePerson->user_id);
        }
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     * @param  array<string, int>  $targetPercentages
     */
    private function randomizeForumDiscussion(Assessment $assessment, Collection $students, array $targetPercentages): void
    {
        $sessions = $this->forumDiscussionScoringService->sessionsInScope($assessment)->values();

        if ($sessions->isEmpty()) {
            return;
        }

        foreach ($students as $coursePerson) {
            $percentage = $targetPercentages[$coursePerson->user_id] ?? random_int(40, 100);
            $metCount = (int) round($sessions->count() * ($percentage / 100));
            $metSessionIds = $sessions->shuffle()->take($metCount)->pluck('id');

            foreach ($sessions as $session) {
                $meetsRequirement = $metSessionIds->contains($session->id);
                $this->randomizeForumPostsForSession($session, $coursePerson->user_id, $meetsRequirement, $assessment);
            }

            $this->forumDiscussionScoringService->recomputeForUser($assessment, $coursePerson->user_id);
        }
    }

    private function randomizeForumPostsForSession(Session $session, string $userId, bool $meetsRequirement, Assessment $assessment): void
    {
        $forum = $this->forumService->findOrCreateForSession($session->id, $assessment->course_id);
        $required = $this->forumDiscussionScoringService->requiredForumPosts($session);
        $target = $meetsRequirement ? $required : random_int(0, max(0, $required - 1));

        foreach ($this->forumThreadService->forUserInSession($userId, $session->id) as $existingThread) {
            $this->forumThreadService->delete($existingThread->id);
        }

        for ($i = 0; $i < $target; $i++) {
            $this->forumThreadService->create([
                'forum_id' => $forum->id,
                'user_id' => $userId,
                'title' => 'Randomly generated discussion #'.($i + 1),
                'description' => 'Randomly generated forum post for development purposes.',
            ]);
        }
    }

    /**
     * Team Assignment scoring is keyed by Group, not by student, so a
     * student who isn't a member of any Group has nothing to grade and its
     * gradebook row stays empty for them — including a student who was
     * enrolled after the course's groups were already formed (e.g. by demo
     * course data, or the Raport bulk generator enrolling students into a
     * pre-existing course). For a dev-only randomizer that's not useful, so
     * any currently-enrolled student not yet in a group gets split into new
     * groups of up to 4 before grading, leaving existing groups untouched.
     *
     * @param  Collection<int, CoursePerson>  $students
     * @return Collection<int, Group>
     */
    private function ensureGroupsForCourse(Course $course, Collection $students): Collection
    {
        $groups = $this->groupService->forCourse($course->id);

        $assignedUserIds = $groups->flatMap(fn (Group $group) => $group->members->pluck('user_id'));

        $unassignedStudents = $students->reject(
            fn (CoursePerson $coursePerson) => $assignedUserIds->contains($coursePerson->user_id)
        )->values();

        if ($unassignedStudents->isEmpty()) {
            return $groups;
        }

        $creatorId = auth()->id();
        $groupNumberOffset = $groups->count();

        foreach ($unassignedStudents->chunk(4) as $index => $chunk) {
            $group = $this->groupService->create([
                'course_id' => $course->id,
                'name' => 'Randomly generated group '.($groupNumberOffset + $index + 1),
                'created_by' => $creatorId,
            ]);

            foreach ($chunk as $coursePerson) {
                $this->groupMemberService->create([
                    'group_id' => $group->id,
                    'user_id' => $coursePerson->user_id,
                    'joined_at' => now(),
                ]);
            }
        }

        return $this->groupService->forCourse($course->id);
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @param  array<string, int>  $targetPercentages
     */
    private function randomizeTeamAssignment(Assessment $assessment, Collection $groups, array $targetPercentages, ?string $graderId): void
    {
        $totalPoints = $this->ensureGradableQuestionPoints($assessment);

        if ($totalPoints <= 0.0) {
            return;
        }

        foreach ($groups as $group) {
            $percentage = $this->groupTargetPercentage($group, $targetPercentages);
            $this->gradeAttempt($assessment, $totalPoints, $percentage, $graderId, groupId: $group->id, userId: null, submittedBy: $this->firstMemberId($group));
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
    private function randomizeIndividualAssessment(Assessment $assessment, Collection $students, array $targetPercentages, ?string $graderId): void
    {
        $totalPoints = $this->ensureGradableQuestionPoints($assessment);

        if ($totalPoints <= 0.0) {
            return;
        }

        foreach ($students as $coursePerson) {
            $percentage = $targetPercentages[$coursePerson->user_id] ?? random_int(40, 100);
            $this->gradeAttempt($assessment, $totalPoints, $percentage, $graderId, groupId: null, userId: $coursePerson->user_id, submittedBy: $coursePerson->user_id);
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
    private function ensureGradableQuestionPoints(Assessment $assessment): float
    {
        $totalPoints = (float) $assessment->questions->sum('points');

        if ($totalPoints > 0.0 || $assessment->questions->isNotEmpty()) {
            return $totalPoints;
        }

        $question = $this->assessmentQuestionService->create([
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
        ?string $graderId,
        ?string $groupId,
        ?string $userId,
        ?string $submittedBy,
    ): void {
        $attempt = $this->assessmentAttemptService->get([
            'assessment_id' => $assessment->id,
            'user_id' => $userId,
            'group_id' => $groupId,
        ])->first();

        if (! $attempt) {
            $attempt = $this->assessmentAttemptService->create([
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

        $existingScore = $this->assessmentScoreService->findByAttempt($attempt->id);
        $data = [
            'assessment_attempt_id' => $attempt->id,
            'score' => $score,
            'graded_by' => $graderId,
            'graded_at' => now(),
            'feedback' => 'Randomly generated score for development purposes.',
        ];

        if ($existingScore) {
            $this->assessmentScoreService->update($existingScore->id, $data);
        } else {
            $this->assessmentScoreService->create($data);
        }
    }
}
