<?php

namespace App\Repositories\LessonMaterialUser;

use App\Models\LessonMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LessonMaterialUserRepository implements LessonMaterialUserRepositoryInterface
{
    public function markAccessed(string $materialId, User $user): void
    {
        $material = LessonMaterial::findOrFail($materialId);

        $existing = $material->users()
            ->wherePivot('user_id', $user->id)
            ->first();

        if ($existing) {
            $material->users()
                ->updateExistingPivot($user->id, [
                    'accessed_at' => now(),
                ]);
        } else {
            $material->users()->attach($user->id, [
                'accessed_at' => now(),
            ]);
        }
    }

    public function isAccessedBy(string $materialId, User $user): bool
    {
        $material = LessonMaterial::findOrFail($materialId);

        return $material->users()
            ->where('user_id', $user->id)
            ->wherePivot('accessed_at', '!=', null, 'and')
            ->exists();
    }

    public function getAccessedCount(string $lessonId, User $user): int
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('lesson_material_user.user_id', $user->id)
                    ->whereNotNull('lesson_material_user.accessed_at');
            })
            ->count();
    }

    /**
     * @param  array<string>  $with
     */
    public function getAccessedMaterials(string $lessonId, User $user, array $with = []): EloquentCollection
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('lesson_material_user.user_id', $user->id)
                    ->whereNotNull('lesson_material_user.accessed_at');
            })
            ->orderBy('order')
            ->with($with)
            ->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function getNotAccessedMaterials(string $lessonId, User $user, array $with = []): EloquentCollection
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('lesson_material_user.user_id', $user->id)
                    ->whereNotNull('lesson_material_user.accessed_at');
            })
            ->orderBy('order')
            ->with($with)
            ->get();
    }
}
