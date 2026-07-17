<?php

namespace App\Repositories\UserLesson;

interface UserLessonRepositoryInterface
{
    /**
     * Mark lesson as completed by user.
     */
    public function markComplete(string $lessonId, mixed $user): void;

    /**
     * Check if lesson is completed by user.
     */
    public function isCompletedBy(string $lessonId, mixed $user): bool;
}
