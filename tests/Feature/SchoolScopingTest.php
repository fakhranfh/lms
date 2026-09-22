<?php

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\User;

test('a query run under school A never returns rows belonging to school B', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    $userA = User::factory()->forSchool($schoolA)->create();
    User::factory()->forSchool($schoolB)->create();

    test()->actingAs($userA);

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->school_id)->toBe($schoolA->id);
});

test('creating a record without school_id assigns the current school automatically', function () {
    $school = School::factory()->create();
    $actingUser = User::factory()->forSchool($school)->create();

    test()->actingAs($actingUser);

    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    expect($user->school_id)->toBe($school->id);
});

test('withoutGlobalScope bypasses school scoping for cross-school console operations', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    $userA = User::factory()->forSchool($schoolA)->create();
    $userB = User::factory()->forSchool($schoolB)->create();

    test()->actingAs($userA);

    $allUserIds = User::withoutGlobalScope(SchoolScope::class)->pluck('id');

    expect($allUserIds)->toContain($userA->id, $userB->id);
});

test('no school context means queries are unscoped', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    $userA = User::factory()->forSchool($schoolA)->create();
    $userB = User::factory()->forSchool($schoolB)->create();

    $userIds = User::all()->pluck('id');

    expect($userIds)->toContain($userA->id, $userB->id);
});
