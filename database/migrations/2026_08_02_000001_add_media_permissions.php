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
            ['name' => 'media.view', 'slug' => 'media-view', 'label' => 'View Media Library'],
            ['name' => 'media.create', 'slug' => 'media-create', 'label' => 'Upload Media'],
            ['name' => 'media.delete', 'slug' => 'media-delete', 'label' => 'Delete Media'],
        ];

        $created = [];
        foreach ($permissions as $permission) {
            $created[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => 'Media']
            );
        }

        // Existing School Admin and Teacher roles were already synced to their
        // respective default permission sets at creation time; grant them the
        // newly added Media permissions too.
        Role::whereIn('name', ['School Admin', 'Teacher'])->get()->each(function (Role $role) use ($created): void {
            $role->givePermissionTo($created);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', ['media.view', 'media.create', 'media.delete'])->delete();
    }
};
