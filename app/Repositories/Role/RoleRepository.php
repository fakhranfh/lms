<?php

namespace App\Repositories\Role;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class RoleRepository implements RoleRepositoryInterface
{
    public function query(array $filters = [])
    {
        $query = Role::query();

        foreach ($filters as $key => $value) {
            if ($key === 'school_id') {
                $query->forSchool($value);

                continue;
            }

            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query;
    }

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->query($filters)->with($with)->get();
    }

    public function getAll(): Collection
    {
        return Role::all();
    }

    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    public function create(array $data): Role
    {
        return Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
            'school_id' => $data['school_id'] ?? null,
        ]);
    }

    public function update(int $id, array $data): Role
    {
        $role = Role::findOrFail($id);
        $role->update([
            'name' => $data['name'],
        ]);

        return $role;
    }

    public function delete(int $id): int
    {
        return Role::destroy($id);
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        $role->syncPermissions($permissions);
    }
}
