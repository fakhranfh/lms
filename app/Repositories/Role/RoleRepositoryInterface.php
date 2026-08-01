<?php

namespace App\Repositories\Role;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    public function query(array $filters = []);

    /**
     * @return Collection<int, Role>
     */
    public function get(array $filters = [], array $with = []): Collection;

    public function getAll(): Collection;

    public function find(int $id): ?Role;

    public function create(array $data): Role;

    public function update(int $id, array $data): Role;

    public function delete(int $id): int;

    public function syncPermissions(Role $role, array $permissionIds): void;

    /**
     * Find a role by name scoped to a school, creating it if it doesn't exist.
     *
     * @param  array<string, mixed>  $extra  Extra attributes to fill when creating
     */
    public function firstOrCreateForSchool(string $schoolId, string $name, array $extra = []): Role;

    /**
     * Determine whether a role has any permissions synced to it.
     */
    public function hasPermissions(Role $role): bool;

    /**
     * Find a role's ID by name (guard-agnostic lookup, e.g. for admin role guarding).
     */
    public function findIdByName(string $name): ?int;

    /**
     * Determine whether any user other than the given one has the named role.
     */
    public function otherUsersHaveRole(string $name, string $excludingUserId): bool;
}
