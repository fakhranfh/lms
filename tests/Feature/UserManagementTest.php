<?php

use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function actingAsUserManager(array $permissionNames): User
{
    $user = User::factory()->create();

    $role = Role::create(['name' => 'user-manager-'.uniqid(), 'guard_name' => 'web']);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

test('users index lists users with their roles', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create(['name' => 'Jane Target']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $target->assignRole($role);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->assertSee('Jane Target')
        ->assertSee('editor');
});

test('user with users.assign-roles can update a target user roles', function () {
    $actor = actingAsUserManager(['users.assign-roles']);
    $target = User::factory()->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [$role->id])
        ->call('updateRoles')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->roles->pluck('id')->all())->toBe([$role->id]);
});

test('updating roles requires users.assign-roles permission even via direct component call', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [$role->id])
        ->call('updateRoles')
        ->assertForbidden();
});

test('removing the last admin role from the only admin user is blocked', function () {
    $actor = actingAsUserManager(['users.assign-roles']);
    $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

    // The create_admin_role_and_assign_admin_user migration seeds its own
    // admin user; remove it so the target below is genuinely the only admin.
    User::role('Admin')->get()->each->delete();

    $target = User::factory()->create();
    $target->assignRole($admin);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [])
        ->call('updateRoles')
        ->assertHasErrors('roles');

    expect($target->fresh()->hasRole('Admin'))->toBeTrue();
});
