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
            ['name' => 'forum.view', 'slug' => 'forum-view', 'label' => 'View Forum'],
            ['name' => 'forum.create', 'slug' => 'forum-create', 'label' => 'Create Forum Posts'],
            ['name' => 'forum.moderate', 'slug' => 'forum-moderate', 'label' => 'Moderate Forum'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Forum']
            );
        }

        $studentPermissions = array_values(array_filter($created, fn (Permission $p) => in_array($p->name, ['forum.view', 'forum.create'])));

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
        Permission::whereIn('name', ['forum.view', 'forum.create', 'forum.moderate'])->delete();
    }
};
