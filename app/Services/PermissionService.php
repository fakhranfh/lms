<?php

namespace App\Services;

use App\Repositories\Permission\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function __construct(protected PermissionRepositoryInterface $permissionRepository) {}

    /**
     * @return Collection<int, Permission>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->permissionRepository->get($filters, $with);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getAll(): Collection
    {
        return $this->permissionRepository->getAll();
    }

    /**
     * @return SupportCollection<string, Collection<int, Permission>>
     */
    public function getAllGrouped(): SupportCollection
    {
        return $this->getAll()->groupBy(fn (Permission $permission): string => $permission->group ?? 'Other');
    }

    public function find(int $id): ?Permission
    {
        return $this->permissionRepository->find($id);
    }
}
