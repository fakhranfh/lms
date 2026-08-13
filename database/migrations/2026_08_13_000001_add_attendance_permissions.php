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
            ['name' => 'attendance.view', 'slug' => 'attendance-view', 'label' => 'View Attendance'],
            ['name' => 'attendance.manage', 'slug' => 'attendance-manage', 'label' => 'Manage Attendance'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Attendance']
            );
        }

        $studentPermissions = array_values(array_filter($created, fn (Permission $p) => $p->name === 'attendance.view'));

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
            'attendance.view',
            'attendance.manage',
        ])->delete();
    }
};
