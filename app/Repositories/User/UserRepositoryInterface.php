<?php

namespace App\Repositories\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function setPendingEmail(User $user, string $pendingEmail): void;

    public function confirmPendingEmail(User $user): void;

    public function getAll(array $with = []): Collection;

    /**
     * Get users with pagination, filters, and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator;

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

    /**
     * Soft delete the user.
     */
    public function delete(User $user): void;

    /**
     * Find a user by email across all schools and including soft-deleted
     * users — email has a hard unique constraint at the database level
     * that isn't school-scoped or soft-delete-aware.
     */
    public function findByEmailAnySchool(string $email, ?string $ignoreUserId = null): ?User;

    /**
     * Determine whether a name is already taken by another user, across
     * all schools.
     */
    public function existsByName(string $name, ?string $ignoreUserId = null): bool;

    /**
     * Whether the given email belongs to a user who is a member of the
     * given school (checked against the school_user pivot directly).
     */
    public function emailBelongsToSchool(string $email, string $schoolId, ?string $ignoreUserId = null): bool;

    /**
     * Find a soft-deleted user with the given email who is a member of the
     * given school, so they can be restored instead of blocked as a duplicate.
     */
    public function findTrashedInSchool(string $email, string $schoolId): ?User;

    /**
     * Restore a soft-deleted user, applying fresh attributes and re-verifying
     * their email since they're being provisioned again by an admin.
     *
     * @param  array<string, mixed>  $data
     */
    public function restore(User $user, array $data): User;
}
