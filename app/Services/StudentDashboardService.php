<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\ForumThread;
use App\Repositories\AssessmentAttempt\AssessmentAttemptRepositoryInterface;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use App\Repositories\SessionMaterialCompletion\SessionMaterialCompletionRepositoryInterface;
use Illuminate\Support\Collection;

class StudentDashboardService
{
    public function __construct(
        private CourseService $courseService,
        private SessionService $sessionService,
        private SessionMaterialCompletionRepositoryInterface $completionRepository,
        private AssessmentService $assessmentService,
        private AssessmentAttemptRepositoryInterface $assessmentAttemptRepository,
        private ForumService $forumService,
        private ForumThreadRepositoryInterface $forumThreadRepository,
    ) {}

    /**
     * @return Collection<int, Course>
     */
    public function enrolledCourses(string $userId): Collection
    {
        return $this->courseService->get(['enrolled_user_id' => $userId]);
    }

    /**
     * Per-course progress combining lesson materials viewed and assessments
     * completed: percentage = (materials done + assessments done) / (total
     * materials + total assessments).
     *
     * @return Collection<int, array{course: Course, percent: int, materials_done: int, materials_total: int, assessments_done: int, assessments_total: int}>
     */
    public function courseProgress(string $userId): Collection
    {
        return $this->enrolledCourses($userId)->map(function (Course $course) use ($userId) {
            $sessions = $this->sessionService->forCourse($course->id, ['materials']);
            $materialsTotal = $sessions->sum(fn ($session) => $session->materials->count());
            $materialsDone = $this->completionRepository->completedCountForSessions($sessions->pluck('id'), $userId);

            $assessments = $this->publishedAssessmentsForCourse($course->id);
            $assessmentsTotal = $assessments->count();
            $assessmentsDone = $assessments->filter(
                fn ($assessment) => $this->isAssessmentCompleted($assessment->id, $userId)
            )->count();

            $total = $materialsTotal + $assessmentsTotal;
            $done = $materialsDone + $assessmentsDone;

            return [
                'course' => $course,
                'percent' => $total === 0 ? 0 : (int) round(min($done, $total) / $total * 100),
                'materials_done' => $materialsDone,
                'materials_total' => $materialsTotal,
                'assessments_done' => $assessmentsDone,
                'assessments_total' => $assessmentsTotal,
            ];
        });
    }

    /**
     * Outstanding lesson materials and assessments across the student's
     * enrolled courses, optionally narrowed to one course and/or one type.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function todoItems(string $userId, ?string $courseId = null, ?string $type = null): Collection
    {
        $items = collect();

        $courses = $this->enrolledCourses($userId)
            ->when($courseId, fn (Collection $courses) => $courses->where('id', $courseId));

        foreach ($courses as $course) {
            if ($type !== 'assessment') {
                $items = $items->merge($this->incompleteMaterials($course, $userId));
            }

            if ($type !== 'material') {
                $items = $items->merge($this->incompleteAssessments($course, $userId));
            }
        }

        return $items->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function incompleteMaterials(Course $course, string $userId): Collection
    {
        $items = collect();
        $sessions = $this->sessionService->forCourse($course->id, ['materials']);

        foreach ($sessions as $session) {
            $completedIds = $this->completionRepository->completedMaterialIds($session->id, $userId);

            foreach ($session->materials as $material) {
                if ($completedIds->contains($material->id)) {
                    continue;
                }

                $items->push([
                    'type' => 'material',
                    'title' => $material->title,
                    'course' => $course,
                    'session' => $session,
                    'url' => route('sessions.index', $course).'?session='.$session->id,
                ]);
            }
        }

        return $items;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function incompleteAssessments(Course $course, string $userId): Collection
    {
        /** @var Collection<int, array<string, mixed>> $items */
        $items = $this->publishedAssessmentsForCourse($course->id)
            ->reject(fn ($assessment) => $this->isAssessmentCompleted($assessment->id, $userId))
            ->map(fn ($assessment) => [
                'type' => 'assessment',
                'title' => $assessment->title,
                'course' => $course,
                'assessment' => $assessment,
                'url' => $this->assessmentShowUrl($assessment),
            ])
            ->values();

        return $items;
    }

    private function assessmentShowUrl(Assessment $assessment): string
    {
        return match ($assessment->type) {
            AssessmentType::TheoryPersonalAssignment => route('assessments.personal.show', $assessment),
            AssessmentType::TheoryTeamAssignment => route('assessments.team.show', $assessment),
            AssessmentType::TheoryQuiz => route('assessments.quiz.show', $assessment),
            AssessmentType::TheoryFinalExam => route('assessments.final-exam.show', $assessment),
            AssessmentType::Attendance => route('assessments.attendance.show', $assessment),
            AssessmentType::ForumDiscussion => route('assessments.forum-discussion.show', $assessment),
        };
    }

    private function publishedAssessmentsForCourse(string $courseId): Collection
    {
        return $this->assessmentService->get([
            'course_id' => $courseId,
            'status' => AssessmentStatus::Published,
        ]);
    }

    private function isAssessmentCompleted(string $assessmentId, string $userId): bool
    {
        $attempts = $this->assessmentAttemptRepository
            ->get(['assessment_id' => $assessmentId, 'user_id' => $userId]);

        foreach ($attempts as $attempt) {
            if ($attempt instanceof AssessmentAttempt && $attempt->submitted_at !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Most recent forum threads across the student's enrolled courses.
     *
     * @return Collection<int, ForumThread>
     */
    public function latestForumPosts(string $userId, int $limit = 5): Collection
    {
        $forumIds = $this->enrolledCourses($userId)
            ->flatMap(fn (Course $course) => $this->forumService->get(['course_id' => $course->id])->pluck('id'))
            ->all();

        if (empty($forumIds)) {
            /** @var Collection<int, ForumThread> $empty */
            $empty = collect();

            return $empty;
        }

        /** @var Collection<int, ForumThread> $threads */
        $threads = $this->forumThreadRepository
            ->forForums($forumIds, ['user', 'forum.course'])
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();

        return $threads;
    }
}
