<?php

namespace App\Repositories\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function update(User $user, array $data): User;

    public function setPendingEmail(User $user, string $pendingEmail): void;

    public function confirmPendingEmail(User $user): void;

    public function getAll(array $with = []): Collection;

    public function find(string $id): ?User;

    public function syncRoles(User $user, array $roleIds): void;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public function firstOrCreate(array $attributes, array $values = []): User;

    /**
     * Determine whether the user has any role assigned.
     */
    public function hasAnyRole(User $user): bool;

    public function assignRole(User $user, Role $role): void;
}
