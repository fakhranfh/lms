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
            ['name' => 'assessment.view', 'slug' => 'assessment-view', 'label' => 'View Assessment'],
            ['name' => 'assessment.create', 'slug' => 'assessment-create', 'label' => 'Create Assessment'],
            ['name' => 'assessment.edit', 'slug' => 'assessment-edit', 'label' => 'Edit Assessment'],
            ['name' => 'assessment.delete', 'slug' => 'assessment-delete', 'label' => 'Delete Assessment'],
            ['name' => 'assessment.grade', 'slug' => 'assessment-grade', 'label' => 'Grade Assessment'],
            ['name' => 'assessment.submit', 'slug' => 'assessment-submit', 'label' => 'Submit Assessment'],
            ['name' => 'groups.manage', 'slug' => 'groups-manage', 'label' => 'Manage Groups'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Assessment']
            );
        }

        $studentPermissions = array_values(array_filter($created, fn (Permission $p) => in_array($p->name, ['assessment.view', 'assessment.submit'])));

        Role::whereIn('name', ['School Admin', 'Teacher'])->get()->each(function (Role $role) use ($created): void {
            $role->givePermissionTo($created);
        });

        Role::where('name', 'Student')->get()->each(function (Role $role) use ($studentPermissions): void {
            $role->givePermissionTo($studentPermissions);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'assessment.view',
            'assessment.create',
            'assessment.edit',
            'assessment.delete',
            'assessment.grade',
            'assessment.submit',
            'groups.manage',
        ])->delete();
    }
};
