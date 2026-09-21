<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Role;
use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Repositories\Role\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        protected RoleRepositoryInterface $roleRepository,
        protected PermissionRepositoryInterface $permissionRepository,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->roleRepository->get($filters, $with);
    }

    /**
     * @return Collection<int, Role>
     */
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

        if ($role && in_array($role->name, [RoleName::Admin->value, RoleName::SchoolAdmin->value], true)) {
            throw ValidationException::withMessages([
                'role' => __('The admin role cannot be deleted.'),
            ]);
        }

        return $this->roleRepository->delete($id);
    }

    /**
     * Create the default School Admin / Teacher / Student roles for a
     * school, if they don't already exist. Used both by DefaultRoleSeeder
     * and automatically when a school is self-registered.
     */
    public function createDefaultRolesForSchool(string $schoolId): void
    {
        DB::transaction(function () use ($schoolId): void {
            $schoolAdminRole = $this->roleRepository->firstOrCreateForSchool(
                $schoolId, RoleName::SchoolAdmin->value, ['slug' => RoleName::SchoolAdmin->slug()]
            );
            $schoolAdminRole->syncPermissions(
                $this->permissionRepository->getByGroups(RoleName::SchoolAdmin->permissionGroups())
            );

            $teacherRole = $this->roleRepository->firstOrCreateForSchool(
                $schoolId, RoleName::Teacher->value, ['slug' => RoleName::Teacher->slug()]
            );
            $teacherRole->syncPermissions(
                $this->permissionRepository->getByNames(RoleName::Teacher->defaultPermissions())
            );

            $studentRole = $this->roleRepository->firstOrCreateForSchool(
                $schoolId, RoleName::Student->value, ['slug' => RoleName::Student->slug()]
            );
            $studentRole->syncPermissions(
                $this->permissionRepository->getByNames(RoleName::Student->defaultPermissions())
            );
        });
    }
}
