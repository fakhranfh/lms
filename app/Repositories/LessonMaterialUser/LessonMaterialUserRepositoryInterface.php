<?php

namespace App\Repositories\LessonMaterialUser;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface LessonMaterialUserRepositoryInterface
{
    /**
     * Mark a material as accessed by a user.
     */
    public function markAccessed(string $materialId, User $user): void;

    /**
     * Check if a material has been accessed by a user.
     */
    public function isAccessedBy(string $materialId, User $user): bool;

    /**
     * Get count of materials accessed by a user in a lesson.
     */
    public function getAccessedCount(string $lessonId, User $user): int;

    /**
     * Get all materials accessed by a user in a lesson.
     *
     * @param  array<string>  $with
     */
    public function getAccessedMaterials(string $lessonId, User $user, array $with = []): Collection;

    /**
     * Get all materials not accessed by a user in a lesson.
     *
     * @param  array<string>  $with
     */
    public function getNotAccessedMaterials(string $lessonId, User $user, array $with = []): Collection;
}
