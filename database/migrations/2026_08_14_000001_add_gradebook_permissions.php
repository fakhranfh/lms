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
            ['name' => 'gradebook.view', 'slug' => 'gradebook-view', 'label' => 'View Gradebook'],
            ['name' => 'gradebook.manage', 'slug' => 'gradebook-manage', 'label' => 'Manage Gradebook'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Gradebook']
            );
        }

        $studentPermissions = array_values(array_filter($created, fn (Permission $p) => $p->name === 'gradebook.view'));

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
            'gradebook.view',
            'gradebook.manage',
        ])->delete();
    }
};
