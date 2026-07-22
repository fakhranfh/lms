<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

class LessonCompletionService
{
    public function __construct(
        protected LessonMaterialService $materialService,
    ) {}

    /**
     * Check if a lesson is completely accessed by a user
     * A lesson is complete when user has accessed ALL materials
     */
    public function isLessonComplete(Lesson $lesson, User $user): bool
    {
        $materials = $lesson->materials()->active()->get();

        // If lesson has no materials, it's not completable
        if ($materials->isEmpty()) {
            return false;
        }

        // Check if all materials are accessed
        $accessedCount = $this->materialService->getAccessedMaterialCount($lesson->id, $user);

        return $accessedCount === $materials->count();
    }

    /**
     * Get lesson progress info
     *
     * @return array{total: int, accessed: int, percentage: float, is_complete: bool}
     */
    public function getLessonProgress(Lesson $lesson, User $user): array
    {
        $materials = $lesson->materials()->active()->get();
        $total = $materials->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'accessed' => 0,
                'percentage' => 0.0,
                'is_complete' => false,
            ];
        }

        $accessed = $this->materialService->getAccessedMaterialCount($lesson->id, $user);
        $percentage = ($accessed / $total) * 100;
        $isComplete = $accessed === $total;

        return [
            'total' => $total,
            'accessed' => $accessed,
            'percentage' => round($percentage, 2),
            'is_complete' => $isComplete,
        ];
    }

    /**
     * Mark lesson as complete if all materials are accessed
     * This updates the lesson_user.completed_at timestamp
     */
    public function markLessonIfComplete(Lesson $lesson, User $user): void
    {
        if ($this->isLessonComplete($lesson, $user)) {
            // Mark lesson as completed
            $lesson->users()->updateExistingPivot($user->id, [
                'completed_at' => now(),
            ]);

            // Fire event if needed
            // event(new LessonCompleted($lesson, $user));
        }
    }

    /**
     * Get progress for all lessons in a module
     *
     * @return Collection<int, array>
     */
    public function getModuleProgress(string $moduleId, User $user): Collection
    {
        $module = Module::findOrFail($moduleId);
        $lessons = $module->lessons;

        return $lessons->map(function (Lesson $lesson) use ($user) {
            return [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'progress' => $this->getLessonProgress($lesson, $user),
            ];
        });
    }

    /**
     * Get progress for all lessons in a course
     *
     * @return Collection<int, array>
     */
    public function getCourseProgress(string $courseId, User $user): Collection
    {
        $course = Course::findOrFail($courseId);
        $modules = $course->modules;

        return $modules->map(function (Module $module) use ($user) {
            $lessonProgress = $this->getModuleProgress($module->id, $user);
            $totalLessons = $lessonProgress->sum(fn ($p) => $p['progress']['total']);
            $completedLessons = $lessonProgress->sum(fn ($p) => $p['progress']['is_complete'] ? 1 : 0);

            return [
                'module_id' => $module->id,
                'module_title' => $module->title,
                'lessons' => $lessonProgress,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0,
            ];
        });
    }
}
