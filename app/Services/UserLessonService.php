<?php

namespace App\Services;

use App\Repositories\UserLesson\UserLessonRepositoryInterface;

class UserLessonService
{
    public function __construct(
        private UserLessonRepositoryInterface $userLessonRepository
    ) {}

    public function markComplete(string $lessonId, mixed $user): void
    {
        $this->userLessonRepository->markComplete($lessonId, $user);
    }

    public function isCompletedBy(string $lessonId, mixed $user): bool
    {
        return $this->userLessonRepository->isCompletedBy($lessonId, $user);
    }
}
