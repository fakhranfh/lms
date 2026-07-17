<?php

namespace App\Repositories\UserLesson;

use App\Models\Lesson;

class UserLessonRepository implements UserLessonRepositoryInterface
{
    public function markComplete(string $lessonId, mixed $user): void
    {
        $lesson = Lesson::findOrFail($lessonId);

        $existing = $lesson->users()
            ->wherePivot('user_id', $user->id)
            ->first();

        if ($existing) {
            $lesson->users()
                ->updateExistingPivot($user->id, [
                    'completed_at' => now(),
                    'last_viewed_at' => now(),
                ]);
        } else {
            $lesson->users()->attach($user->id, [
                'completed_at' => now(),
                'last_viewed_at' => now(),
            ]);
        }
    }

    public function isCompletedBy(string $lessonId, mixed $user): bool
    {
        $lesson = Lesson::findOrFail($lessonId);

        return $lesson->users()
            ->where('user_id', $user->id)
            ->where('completed_at', '!=', null)
            ->exists();
    }
}
