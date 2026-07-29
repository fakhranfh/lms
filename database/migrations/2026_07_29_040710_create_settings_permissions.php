<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permissions for billing settings management.
     *
     * @var array<string, array{label: string, group: string}>
     */
    private array $defaultPermissions = [
        'settings.view' => ['label' => 'View Settings', 'group' => 'Settings'],
        'settings.update' => ['label' => 'Update Settings', 'group' => 'Settings'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($this->defaultPermissions)
            ->map(fn (array $attributes, string $name) => Permission::updateOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ], [
                'label' => $attributes['label'],
                'group' => $attributes['group'],
            ]));

        $adminRole = Role::where('name', 'Admin')->first();

        if ($adminRole) {
            $existingPermissions = $adminRole->permissions;
            $allPermissions = $existingPermissions->merge($permissions);
            $adminRole->syncPermissions($allPermissions);
        }
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', array_keys($this->defaultPermissions))->delete();
    }
};
