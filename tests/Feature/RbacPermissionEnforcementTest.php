<?php

use App\Livewire\Roles\RoleCreate;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('user without any permission is forbidden from role management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
});

test('user with roles.view permission can view but not create roles', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'roles-viewer', 'guard_name' => 'web'])
        ->givePermissionTo(Permission::firstOrCreate(['name' => 'roles.view', 'guard_name' => 'web']));
    $user->assignRole('roles-viewer');

    $this->actingAs($user)->get(route('roles.index'))->assertOk();
    $this->actingAs($user)->get(route('roles.create'))->assertForbidden();

    // Even if a user could reach the create page, the component's own
    // action method must re-check permissions (route middleware alone
    // does not protect Livewire's update endpoint).
    Livewire::actingAs($user)->test(RoleCreate::class)
        ->set('name', 'new-role')
        ->call('store')
        ->assertForbidden();
});

test('user with permissions.view permission cannot access role management', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'permissions-viewer', 'guard_name' => 'web'])
        ->givePermissionTo(Permission::firstOrCreate(['name' => 'permissions.view', 'guard_name' => 'web']));
    $user->assignRole('permissions-viewer');

    $this->actingAs($user)->get(route('permissions.index'))->assertOk();
    $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
});

test('user with users.assign-roles permission can access the role assignment page', function () {
    $school = School::factory()->create();
    $target = User::factory()->forSchool($school)->create();
    $actor = User::factory()->forSchool($school)->create();

    Role::create(['name' => 'role-assigner', 'guard_name' => 'web'])
        ->givePermissionTo(Permission::firstOrCreate(['name' => 'users.assign-roles', 'guard_name' => 'web']));
    $actor->assignRole('role-assigner');

    $this->actingAs($actor)->get(route('users.roles.edit', $target))->assertOk();
});
