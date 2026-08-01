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
            ['name' => 'users.delete', 'guard_name' => 'web'],
            ['slug' => 'users-delete', 'label' => 'Delete User', 'group' => 'Users']
        );

        // Existing School Admin roles were already synced to the "Users" permission
        // group at creation time; grant them the newly added permission too.
        Role::where('name', 'School Admin')->get()->each(function (Role $role) use ($permission): void {
            $role->givePermissionTo($permission);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'users.delete')->delete();
    }
};
