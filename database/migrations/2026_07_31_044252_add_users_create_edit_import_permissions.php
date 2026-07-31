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
            ['name' => 'users.create', 'slug' => 'users-create', 'label' => 'Create User', 'group' => 'Users'],
            ['name' => 'users.edit', 'slug' => 'users-edit', 'label' => 'Edit User', 'group' => 'Users'],
            ['name' => 'users.import', 'slug' => 'users-import', 'label' => 'Import Users', 'group' => 'Users'],
        ];

        $created = collect();

        foreach ($permissions as $permission) {
            $created->push(Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => $permission['group']]
            ));
        }

        // Existing School Admin roles were already synced to the "Users" permission
        // group at creation time; grant them the newly added permissions too.
        Role::where('name', 'School Admin')->get()->each(function (Role $role) use ($created): void {
            $role->givePermissionTo($created);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', ['users.create', 'users.edit', 'users.import'])->delete();
    }
};
