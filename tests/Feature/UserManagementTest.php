<?php

use App\Enums\RoleName;
use App\Livewire\Users\UserRoles;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function actingAsUserManager(array $permissionNames, ?School $school = null): User
{
    $user = $school ? User::factory()->forSchool($school)->create() : User::factory()->create();

    $role = Role::create(['name' => 'user-manager-'.uniqid(), 'guard_name' => 'web']);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

test('user with users.assign-roles can update a target user roles', function () {
    $school = School::factory()->create();
    $actor = actingAsUserManager(['users.assign-roles'], $school);
    $target = User::factory()->forSchool($school)->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [$role->id])
        ->call('updateRoles')
        ->assertHasNoErrors()
        ->assertRedirect(route('roles.index'));

    expect($target->fresh()->roles->pluck('id')->all())->toBe([$role->id]);
});

test('updating roles requires users.assign-roles permission even via direct component call', function () {
    $school = School::factory()->create();
    $actor = actingAsUserManager(['users.view'], $school);
    $target = User::factory()->forSchool($school)->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [$role->id])
        ->call('updateRoles')
        ->assertForbidden();
});

test('removing the last admin role from the only admin user is blocked', function () {
    $school = School::factory()->create();
    $actor = actingAsUserManager(['users.assign-roles'], $school);
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    // The create_admin_role_and_assign_admin_user migration seeds its own
    // admin user; remove it so the target below is genuinely the only admin.
    User::role(RoleName::Admin)->get()->each->delete();

    $target = User::factory()->forSchool($school)->create();
    $target->assignRole($admin);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [])
        ->call('updateRoles')
        ->assertHasErrors('roles');

    expect($target->fresh()->hasRole(RoleName::Admin))->toBeTrue();
});
