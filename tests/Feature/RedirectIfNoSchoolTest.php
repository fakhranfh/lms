<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\School;
use App\Models\User;

test('a school-less admin is redirected to register their school', function () {
    $user = User::factory()->create(['school_id' => null]);
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertRedirect(route('get-started.school'))
        ->assertSessionHas('status', 'You need to register a school before you can continue.');
});

test('an admin with a school is not redirected', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $school->admins()->attach($user->id);
    $role = Role::create([
        'name' => RoleName::SchoolAdmin->value,
        'slug' => 'school-admin-'.$school->id,
        'guard_name' => 'web',
        'school_id' => $school->id,
    ]);
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk();
});

test('a platform admin without a school is not redirected', function () {
    $user = User::factory()->create(['school_id' => null]);
    $role = Role::firstOrCreate(
        ['name' => RoleName::Admin->value, 'school_id' => null],
        ['slug' => 'admin', 'guard_name' => 'web']
    );
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk();
});
