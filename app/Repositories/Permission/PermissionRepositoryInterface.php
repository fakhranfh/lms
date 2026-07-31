<?php

namespace App\Repositories\Permission;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

interface PermissionRepositoryInterface
{
    public function query(array $filters = []);

    /**
     * @return Collection<int, Permission>
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * @return Collection<int, Permission>
     */
    public function getAll(): Collection;

    public function find(int $id): ?Permission;

    /**
     * All permissions except the given one (e.g. excluding billing).
     *
     * @return Collection<int, Permission>
     */
    public function getAllExcept(string $name): Collection;

    /**
     * Permissions matching the given set of names.
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Permission>
     */
    public function getByNames(array $names): Collection;

    /**
     * View-only permissions under a namespace (e.g. "courses.%view").
     *
     * @return Collection<int, Permission>
     */
    public function getViewPermissionsFor(string $namespace): Collection;

    /**
     * Permissions belonging to the given groups (e.g. "Users", "Roles").
     *
     * @param  array<int, string>  $groups
     * @return Collection<int, Permission>
     */
    public function getByGroups(array $groups): Collection;
}
