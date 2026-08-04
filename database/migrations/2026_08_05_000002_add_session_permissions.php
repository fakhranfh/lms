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
            ['name' => 'sessions.view', 'slug' => 'sessions-view', 'label' => 'View Sessions'],
            ['name' => 'sessions.create', 'slug' => 'sessions-create', 'label' => 'Create Sessions'],
            ['name' => 'sessions.edit', 'slug' => 'sessions-edit', 'label' => 'Edit Sessions'],
            ['name' => 'sessions.delete', 'slug' => 'sessions-delete', 'label' => 'Delete Sessions'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Sessions']
            );
        }

        $viewOnly = array_values(array_filter($created, fn (Permission $p) => $p->name === 'sessions.view'));

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
        Permission::whereIn('name', ['sessions.view', 'sessions.create', 'sessions.edit', 'sessions.delete'])->delete();
    }
};
