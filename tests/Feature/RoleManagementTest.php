<?php

use App\Enums\RoleName;
use App\Livewire\Roles\RoleCreate;
use App\Livewire\Roles\RoleEdit;
use App\Livewire\Roles\RoleIndex;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function actingAsRoleManager(array $permissionNames): User
{
    $user = User::factory()->create();

    $role = Role::create(['name' => 'role-manager-'.uniqid(), 'guard_name' => 'web']);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

test('roles index lists existing roles', function () {
    $user = actingAsRoleManager(['roles.view']);
    Role::create(['name' => 'editors', 'guard_name' => 'web', 'school_id' => $user->school_id]);

    Livewire::actingAs($user)->test(RoleIndex::class)
        ->assertSee('editors');
});

test('roles index does not list roles belonging to another school', function () {
    $user = actingAsRoleManager(['roles.view']);
    $otherSchool = School::factory()->create();
    Role::create(['name' => 'other-school-role', 'guard_name' => 'web', 'school_id' => $otherSchool->id]);

    Livewire::actingAs($user)->test(RoleIndex::class)
        ->assertDontSee('other-school-role');
});

test('user with roles.create can create a role with permissions', function () {
    $user = actingAsRoleManager(['roles.view', 'roles.create']);
    $permission = Permission::firstOrCreate(['name' => 'widgets.view', 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleCreate::class)
        ->set('name', 'widget-manager')
        ->set('permissions', [$permission->id])
        ->call('store')
        ->assertHasNoErrors()
        ->assertRedirect(route('roles.index'));

    $role = Role::where('name', 'widget-manager')->first();
    expect($role)->not->toBeNull();
    expect($role->permissions->pluck('id')->all())->toBe([$permission->id]);
});

test('role name is required and must be unique on create', function () {
    $user = actingAsRoleManager(['roles.view', 'roles.create']);
    Role::create(['name' => 'duplicate-role', 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleCreate::class)
        ->set('name', 'duplicate-role')
        ->call('store')
        ->assertHasErrors('name');
});

test('user with roles.update can update a role and its permissions', function () {
    $user = actingAsRoleManager(['roles.view', 'roles.update']);
    $role = Role::create(['name' => 'support', 'guard_name' => 'web']);
    $permission = Permission::firstOrCreate(['name' => 'tickets.view', 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleEdit::class, ['role' => $role])
        ->set('name', 'support-team')
        ->set('permissions', [$permission->id])
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('roles.index'));

    $role->refresh();
    expect($role->name)->toBe('support-team');
    expect($role->permissions->pluck('id')->all())->toBe([$permission->id]);
});

test('admin role name cannot be changed even if submitted', function () {
    $user = actingAsRoleManager(['roles.view', 'roles.update']);
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleEdit::class, ['role' => $admin])
        ->set('name', 'super-admin')
        ->call('update');

    $admin->refresh();
    expect($admin->name)->toBe(RoleName::Admin->value);
});

test('user with roles.delete can delete a non-admin role', function () {
    $user = actingAsRoleManager(['roles.view', 'roles.delete']);
    $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleIndex::class)
        ->call('destroy', $role->id)
        ->assertSet('successMessage', 'Role deleted successfully.');

    expect(Role::find($role->id))->toBeNull();
});

test('admin role cannot be deleted', function () {
    $user = actingAsRoleManager(['roles.view', 'roles.delete']);
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleIndex::class)
        ->call('destroy', $admin->id)
        ->assertSet('successMessage', null);

    expect(Role::find($admin->id))->not->toBeNull();
});

test('deleting a role requires roles.delete permission even from the index page', function () {
    $user = actingAsRoleManager(['roles.view']);
    $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

    Livewire::actingAs($user)->test(RoleIndex::class)
        ->call('destroy', $role->id)
        ->assertForbidden();

    expect(Role::find($role->id))->not->toBeNull();
});
