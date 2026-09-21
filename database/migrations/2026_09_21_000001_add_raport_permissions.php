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
            ['name' => 'raport.view', 'guard_name' => 'web'],
            ['slug' => 'raport-view', 'label' => 'View Raport', 'group' => 'Raport']
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
        Permission::where('name', 'raport.view')->delete();
    }
};
