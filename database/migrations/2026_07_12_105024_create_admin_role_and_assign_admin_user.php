<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permissions that gate every admin-facing feature currently in the app,
     * keyed by name with a human-readable label and a group they belong to.
     * Permission management itself is view-only, so there is no
     * permissions.create/update/delete.
     *
     * @var array<string, array{label: string, group: string}>
     */
    private array $defaultPermissions = [
        'roles.view' => ['label' => 'View Roles', 'group' => 'Roles'],
        'roles.create' => ['label' => 'Create Roles', 'group' => 'Roles'],
        'roles.update' => ['label' => 'Update Roles', 'group' => 'Roles'],
        'roles.delete' => ['label' => 'Delete Roles', 'group' => 'Roles'],
        'permissions.view' => ['label' => 'View Permissions', 'group' => 'Permissions'],
        'users.view' => ['label' => 'View Users', 'group' => 'Users'],
        'users.assign-roles' => ['label' => 'Assign User Roles', 'group' => 'Users'],
    ];

    /**
     * Run the migrations.
     */
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

        $adminRole->syncPermissions($permissions);

        $adminUser = User::query()->oldest('id')->first();

        if (! $adminUser) {
            $adminUser = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        if (! $adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', array_keys($this->defaultPermissions))->delete();
        Role::where('name', 'admin')->delete();
    }
};
