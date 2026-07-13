<?php

namespace App\Services;

use App\Repositories\Permission\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function __construct(protected PermissionRepositoryInterface $permissionRepository) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->permissionRepository->get($filters, $with);
    }

    public function getAll(): Collection
    {
        return $this->permissionRepository->getAll();
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public function getAllGrouped(): Collection
    {
        return $this->getAll()->groupBy(fn (Permission $permission) => $permission->group ?? 'Other');
    }

    public function find(int $id): ?Permission
    {
        return $this->permissionRepository->find($id);
    }
}
