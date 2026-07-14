<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permissions for pricing tier management.
     *
     * @var array<string, array{label: string, group: string}>
     */
    private array $defaultPermissions = [
        'pricing-tiers.view' => ['label' => 'View Pricing Tiers', 'group' => 'Pricing Tiers'],
        'pricing-tiers.create' => ['label' => 'Create Pricing Tiers', 'group' => 'Pricing Tiers'],
        'pricing-tiers.update' => ['label' => 'Update Pricing Tiers', 'group' => 'Pricing Tiers'],
        'pricing-tiers.delete' => ['label' => 'Delete Pricing Tiers', 'group' => 'Pricing Tiers'],
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

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $existingPermissions = $adminRole->permissions;
        $allPermissions = $existingPermissions->merge($permissions);
        $adminRole->syncPermissions($allPermissions);
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', array_keys($this->defaultPermissions))->delete();
    }
};
