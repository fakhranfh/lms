<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\School;
use App\Models\User;

function actingAsSchoolUser(School $school, RoleName $roleName): User
{
    $user = User::factory()->create(['school_id' => $school->id]);

    $role = Role::firstOrCreate(
        ['school_id' => $school->id, 'name' => $roleName->value],
        ['guard_name' => 'web', 'slug' => $roleName->slug()]
    );

    $user->assignRole($role);

    return $user;
}

test('teacher does not see tier management in the sidebar', function () {
    $school = School::factory()->create();
    $user = actingAsSchoolUser($school, RoleName::Teacher);

    $this->actingAs($user)->get("http://{$school->domain}/dashboard")
        ->assertDontSee('Tier Management');
});

test('school admin does not see tier management in the sidebar', function () {
    $school = School::factory()->create();
    $user = actingAsSchoolUser($school, RoleName::SchoolAdmin);

    $this->actingAs($user)->get("http://{$school->domain}/dashboard")
        ->assertDontSee('Tier Management');
});

test('teacher cannot access tier management page directly', function () {
    $school = School::factory()->create();
    $user = actingAsSchoolUser($school, RoleName::Teacher);

    $this->actingAs($user)->get("http://{$school->domain}/tier-management")
        ->assertForbidden();
});

test('school admin can access tier management page directly', function () {
    $school = School::factory()->create();
    $user = actingAsSchoolUser($school, RoleName::SchoolAdmin);

    $this->actingAs($user)->get("http://{$school->domain}/tier-management")
        ->assertOk();
});
