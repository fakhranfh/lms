<?php

use App\Models\Course;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\School;
use App\Models\User;

function runAddDemoStudentToCourseGroupsMigration(): object
{
    return require database_path('migrations/2026_08_12_000002_add_demo_student_to_course_groups.php');
}

/**
 * The "School Demo" school and its "Demo Student" user already exist from
 * migration 2026_07_25_000003, which runs as part of RefreshDatabase.
 */
function demoSchoolForGroups(): School
{
    return School::where('name', 'School Demo')->firstOrFail();
}

function demoStudentUserForGroups(): User
{
    return User::where('name', 'Demo Student')->firstOrFail();
}

test('migration adds the demo student to a group in every course that has groups', function () {
    $school = demoSchoolForGroups();
    $demoStudent = demoStudentUserForGroups();
    $course = Course::factory()->for($school)->create();
    $group = Group::factory()->for($course)->create();

    runAddDemoStudentToCourseGroupsMigration()->up();

    expect(GroupMember::where('group_id', $group->id)->where('user_id', $demoStudent->id)->exists())->toBeTrue();
});

test('migration only joins one group per course', function () {
    $school = demoSchoolForGroups();
    $demoStudent = demoStudentUserForGroups();
    $course = Course::factory()->for($school)->create();
    $groups = Group::factory()->for($course)->count(2)->create();

    runAddDemoStudentToCourseGroupsMigration()->up();

    $joinedCount = GroupMember::where('user_id', $demoStudent->id)
        ->whereIn('group_id', $groups->pluck('id'))
        ->count();

    expect($joinedCount)->toBe(1);
});

test('migration does not duplicate an existing group membership', function () {
    $school = demoSchoolForGroups();
    $demoStudent = demoStudentUserForGroups();
    $course = Course::factory()->for($school)->create();
    $group = Group::factory()->for($course)->create();
    GroupMember::factory()->for($group)->create(['user_id' => $demoStudent->id]);

    runAddDemoStudentToCourseGroupsMigration()->up();

    expect(GroupMember::where('group_id', $group->id)->where('user_id', $demoStudent->id)->count())->toBe(1);
});

test('migration skips courses without any groups', function () {
    $school = demoSchoolForGroups();
    Course::factory()->for($school)->create();

    runAddDemoStudentToCourseGroupsMigration()->up();

    expect(GroupMember::count())->toBe(0);
});

test('migration is a no-op when there is no demo school', function () {
    demoSchoolForGroups()->delete();

    runAddDemoStudentToCourseGroupsMigration()->up();

    expect(GroupMember::count())->toBe(0);
});

test('down removes the group memberships the migration created', function () {
    $school = demoSchoolForGroups();
    $demoStudent = demoStudentUserForGroups();
    $course = Course::factory()->for($school)->create();
    $group = Group::factory()->for($course)->create();

    $migration = runAddDemoStudentToCourseGroupsMigration();
    $migration->up();

    expect(GroupMember::where('group_id', $group->id)->where('user_id', $demoStudent->id)->exists())->toBeTrue();

    $migration->down();

    expect(GroupMember::where('group_id', $group->id)->where('user_id', $demoStudent->id)->exists())->toBeFalse();
});
