<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

function actingAsPolicyUser(array $permissionNames, bool $isAdmin = false): User
{
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);

    $roleName = $isAdmin ? RoleName::Admin->value : 'policy-tester-'.uniqid();
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'school_id' => null]);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

test('role policy allows admin with roles.create permission to create roles', function () {
    $user = actingAsPolicyUser(['roles.view', 'roles.create'], isAdmin: true);

    expect($user->can('create', Role::class))->toBeTrue();
});

test('role policy denies non-admin from creating roles', function () {
    $user = actingAsPolicyUser(['roles.view', 'roles.create'], isAdmin: false);

    expect($user->can('create', Role::class))->toBeFalse();
});

test('gate permission and role helpers work as expected', function () {
    $user = actingAsPolicyUser(['roles.view']);

    expect(Gate::forUser($user)->allows('permission', 'roles.view'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('permission', 'roles.delete'))->toBeFalse();
});
