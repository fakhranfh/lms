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
            ['name' => 'students.view', 'slug' => 'students-view', 'label' => 'View Students'],
            ['name' => 'students.create', 'slug' => 'students-create', 'label' => 'Create Students'],
            ['name' => 'students.edit', 'slug' => 'students-edit', 'label' => 'Edit Students'],
            ['name' => 'students.delete', 'slug' => 'students-delete', 'label' => 'Delete Students'],
            ['name' => 'students.import', 'slug' => 'students-import', 'label' => 'Import Students'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Students']
            );
        }

        // Existing School Admin and Teacher roles were already synced to their
        // respective default permission sets at creation time; grant them the
        // newly added Student-management permissions too.
        Role::whereIn('name', ['School Admin', 'Teacher'])->get()->each(function (Role $role) use ($created): void {
            $role->givePermissionTo($created);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import',
        ])->delete();
    }
};
