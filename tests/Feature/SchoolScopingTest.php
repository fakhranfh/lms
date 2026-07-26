<?php

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use App\Support\CurrentSchool;

test('a query run under school A never returns rows belonging to school B', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    User::factory()->forSchool($schoolA)->create();
    User::factory()->forSchool($schoolB)->create();

    app(CurrentSchool::class)->setSchoolId($schoolA->id);

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->school_id)->toBe($schoolA->id);
});

test('creating a record without school_id assigns the current school automatically', function () {
    $school = School::factory()->create();

    app(CurrentSchool::class)->setSchoolId($school->id);

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

    app(CurrentSchool::class)->setSchoolId($schoolA->id);

    $allUserIds = User::withoutGlobalScope(SchoolScope::class)->pluck('id');

    expect($allUserIds)->toContain($userA->id, $userB->id);
});

test('no school context means queries are unscoped', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    $userA = User::factory()->forSchool($schoolA)->create();
    $userB = User::factory()->forSchool($schoolB)->create();

    app(CurrentSchool::class)->setSchoolId(null);

    $userIds = User::all()->pluck('id');

    expect($userIds)->toContain($userA->id, $userB->id);
});
