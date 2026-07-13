<?php

namespace App\Services;

use App\Repositories\Role\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(protected RoleRepositoryInterface $roleRepository) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->roleRepository->get($filters, $with);
    }

    public function getAll(): Collection
    {
        return $this->roleRepository->getAll();
    }

    public function find(int $id): ?Role
    {
        return $this->roleRepository->find($id);
    }

    public function create(array $data): Role
    {
        $role = $this->roleRepository->create($data);

        $this->roleRepository->syncPermissions($role, $data['permissions'] ?? []);

        return $role;
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->roleRepository->update($id, $data);

        $this->roleRepository->syncPermissions($role, $data['permissions'] ?? []);

        return $role;
    }

    public function delete(int $id): int
    {
        $role = $this->roleRepository->find($id);

        if ($role && $role->name === 'admin') {
            throw ValidationException::withMessages([
                'role' => __('The admin role cannot be deleted.'),
            ]);
        }

        return $this->roleRepository->delete($id);
    }
}
