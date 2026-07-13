<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface UserRepositoryInterface
{
    public function update(User $user, array $data): User;

    public function updateProfilePhoto(User $user, UploadedFile $photo): string;

    public function removeProfilePhoto(User $user): void;

    public function setPendingEmail(User $user, string $pendingEmail): void;

    public function confirmPendingEmail(User $user): void;

    public function getAll(array $with = []): Collection;

    public function find(int $id): ?User;

    public function syncRoles(User $user, array $roleIds): void;
}
