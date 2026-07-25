<?php

namespace App\Repositories\Permission;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

interface PermissionRepositoryInterface
{
    public function query(array $filters = []);

    public function get(array $filters = [], array $with = []): Collection;

    public function getAll(): Collection;

    public function find(int $id): ?Permission;

    /**
     * All permissions except the given one (e.g. excluding billing).
     */
    public function getAllExcept(string $name): Collection;

    /**
     * Permissions matching the given set of names.
     *
     * @param  array<int, string>  $names
     */
    public function getByNames(array $names): Collection;

    /**
     * View-only permissions under a namespace (e.g. "courses.%view").
     */
    public function getViewPermissionsFor(string $namespace): Collection;
}
