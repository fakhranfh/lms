<?php

use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\User;

function runEnrollDemoStudentMigration(): object
{
    return require database_path('migrations/2026_08_12_000001_enroll_demo_student_into_all_demo_courses.php');
}

/**
 * The "School Demo" school and its "Demo Student" user already exist from
 * migration 2026_07_25_000003, which runs as part of RefreshDatabase.
 */
function demoSchool(): School
{
    return School::where('name', 'School Demo')->firstOrFail();
}

function demoStudentUser(): User
{
    return User::where('name', 'Demo Student')->firstOrFail();
}

test('migration enrolls the demo student into every course of the demo school', function () {
    $school = demoSchool();
    $demoStudent = demoStudentUser();
    $courses = Course::factory()->for($school)->count(3)->create();

    runEnrollDemoStudentMigration()->up();

    foreach ($courses as $course) {
        expect(CoursePerson::where('course_id', $course->id)
            ->where('user_id', $demoStudent->id)
            ->where('role_in_course', 'student')
            ->exists())->toBeTrue();
    }
});

test('migration does not duplicate an existing enrollment', function () {
    $school = demoSchool();
    $demoStudent = demoStudentUser();
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->create([
        'user_id' => $demoStudent->id,
        'role_in_course' => 'teacher',
    ]);

    runEnrollDemoStudentMigration()->up();

    expect(CoursePerson::where('course_id', $course->id)->where('user_id', $demoStudent->id)->count())->toBe(1);
});

test('migration is a no-op when there is no demo school', function () {
    demoSchool()->delete();

    runEnrollDemoStudentMigration()->up();

    expect(CoursePerson::count())->toBe(0);
});

test('down removes the student enrollments the migration created', function () {
    $school = demoSchool();
    $demoStudent = demoStudentUser();
    $course = Course::factory()->for($school)->create();

    $migration = runEnrollDemoStudentMigration();
    $migration->up();

    expect(CoursePerson::where('course_id', $course->id)->where('user_id', $demoStudent->id)->exists())->toBeTrue();

    $migration->down();

    expect(CoursePerson::where('course_id', $course->id)->where('user_id', $demoStudent->id)->exists())->toBeFalse();
});
