<?php

use App\Enums\RoleName;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use App\Models\School;
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

test('users index search filters users by name or email', function () {
    $actor = actingAsUserManager(['users.view']);
    User::factory()->create(['name' => 'Jane Target', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'Someone Else', 'email' => 'else@example.com']);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->set('search', 'Jane')
        ->assertSee('Jane Target')
        ->assertDontSee('Someone Else');
});

test('users index filters users by role', function () {
    $actor = actingAsUserManager(['users.view']);
    $editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'web']);

    $editor = User::factory()->create(['name' => 'Editor User']);
    $editor->assignRole($editorRole);

    $viewer = User::factory()->create(['name' => 'Viewer User']);
    $viewer->assignRole($viewerRole);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->set('filterRole', $editorRole->id)
        ->assertSee('Editor User')
        ->assertDontSee('Viewer User');
});

test('users index role filter only lists roles scoped to the current school', function () {
    $actor = actingAsUserManager(['users.view']);
    App\Models\Role::create(['name' => 'own-school-role', 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $otherSchool = School::factory()->create();
    App\Models\Role::create(['name' => 'other-school-role', 'guard_name' => 'web', 'school_id' => $otherSchool->id]);

    $component = Livewire::actingAs($actor)->test(UserIndex::class);

    expect($component->instance()->availableRoles()->pluck('name')->all())
        ->toContain('own-school-role')
        ->not->toContain('other-school-role');
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
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    // The create_admin_role_and_assign_admin_user migration seeds its own
    // admin user; remove it so the target below is genuinely the only admin.
    User::role(RoleName::Admin)->get()->each->delete();

    $target = User::factory()->create();
    $target->assignRole($admin);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [])
        ->call('updateRoles')
        ->assertHasErrors('roles');

    expect($target->fresh()->hasRole(RoleName::Admin))->toBeTrue();
});
