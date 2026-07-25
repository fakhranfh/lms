<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Role\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

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

        if ($role && in_array($role->name, [RoleName::Admin->value, RoleName::SchoolAdmin->value], true)) {
            throw ValidationException::withMessages([
                'role' => __('The admin role cannot be deleted.'),
            ]);
        }

        return $this->roleRepository->delete($id);
    }

    /**
     * Create the default School Admin / Instructor / Student roles for a
     * school, if they don't already exist. Used both by DefaultRoleSeeder
     * and automatically when a school is self-registered.
     */
    public function createDefaultRolesForSchool(string $schoolId): void
    {
        $schoolAdminRole = Role::firstOrCreate(
            ['name' => RoleName::SchoolAdmin->value, 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => RoleName::SchoolAdmin->slug()]
        );

        $schoolAdminRole->syncPermissions(Permission::where('name', '!=', 'settings.billing')->get());

        $instructorRole = Role::firstOrCreate(
            ['name' => RoleName::Instructor->value, 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => RoleName::Instructor->slug()]
        );

        $instructorRole->syncPermissions(
            Permission::whereIn('name', RoleName::Instructor->defaultPermissions())->get()
        );

        $studentRole = Role::firstOrCreate(
            ['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $schoolId],
            ['slug' => RoleName::Student->slug()]
        );

        $studentRole->syncPermissions(
            Permission::whereIn('name', RoleName::Student->defaultPermissions())->get()
        );
    }
}
