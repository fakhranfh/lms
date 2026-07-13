<?php

namespace App\Repositories\Role;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function query(array $filters = []);

    public function get(array $filters = [], array $with = []): Collection;

    public function getAll(): Collection;

    public function find(int $id): ?Role;

    public function create(array $data): Role;

    public function update(int $id, array $data): Role;

    public function delete(int $id): int;

    public function syncPermissions(Role $role, array $permissionIds): void;
}
