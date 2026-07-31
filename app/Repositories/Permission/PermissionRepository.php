<?php

namespace App\Repositories\Permission;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function query(array $filters = [])
    {
        $query = Permission::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->query($filters)->with($with)->get();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getAll(): Collection
    {
        return Permission::all();
    }

    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getAllExcept(string $name): Collection
    {
        return Permission::where('name', '!=', $name)->get();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getByNames(array $names): Collection
    {
        return Permission::whereIn('name', $names)->get();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getViewPermissionsFor(string $namespace): Collection
    {
        return Permission::where('name', 'like', "{$namespace}.%")
            ->where('name', 'like', '%view')
            ->get();
    }
}
