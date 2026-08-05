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
            ['name' => 'syllabus.view', 'slug' => 'syllabus-view', 'label' => 'View Syllabus'],
            ['name' => 'syllabus.edit', 'slug' => 'syllabus-edit', 'label' => 'Edit Syllabus'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Syllabus']
            );
        }

        $viewOnly = array_values(array_filter($created, fn (Permission $p) => $p->name === 'syllabus.view'));

        Role::whereIn('name', ['School Admin', 'Teacher'])->get()->each(function (Role $role) use ($created): void {
            $role->givePermissionTo($created);
        });

        Role::where('name', 'Student')->get()->each(function (Role $role) use ($viewOnly): void {
            $role->givePermissionTo($viewOnly);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', ['syllabus.view', 'syllabus.edit'])->delete();
    }
};
