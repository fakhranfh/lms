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
        $permission = Permission::firstOrCreate(
            ['name' => 'people.view', 'guard_name' => 'web'],
            ['slug' => 'people-view', 'label' => 'View People', 'group' => 'People']
        );

        Role::whereIn('name', ['School Admin', 'Teacher', 'Student'])->get()->each(function (Role $role) use ($permission): void {
            $role->givePermissionTo($permission);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'people.view')->delete();
    }
};
