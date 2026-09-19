<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            ['name' => 'teachers.view', 'slug' => 'teachers-view', 'label' => 'View Teachers'],
            ['name' => 'teachers.create', 'slug' => 'teachers-create', 'label' => 'Create Teachers'],
            ['name' => 'teachers.edit', 'slug' => 'teachers-edit', 'label' => 'Edit Teachers'],
            ['name' => 'teachers.delete', 'slug' => 'teachers-delete', 'label' => 'Delete Teachers'],
            ['name' => 'teachers.import', 'slug' => 'teachers-import', 'label' => 'Import Teachers'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Teachers']
            );
        }

        // Only School Admin manages teachers — teachers themselves must not
        // be able to manage other teachers.
        Role::where('name', 'School Admin')->get()->each(function (Role $role) use ($created): void {
            $role->givePermissionTo($created);
        });

        // Teachers previously had full student-management permissions
        // (RoleName::Teacher::defaultPermissions()); that access now belongs
        // to School Admin only.
        $studentPermissions = Permission::whereIn('name', [
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import',
        ])->get();

        Role::where('name', 'Teacher')->get()->each(function (Role $role) use ($studentPermissions): void {
            $role->revokePermissionTo($studentPermissions);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete', 'teachers.import',
        ])->delete();
    }
};
