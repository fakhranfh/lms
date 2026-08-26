<?php

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;

test('student can fetch their own session breakdown for an expandable type', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $school->id]));
    $student->givePermissionTo('gradebook.view');
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);

    Assessment::factory()->for($course)->create(['type' => 'attendance', 'weight' => 10, 'start_date' => null, 'end_date' => null]);
    $session = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $student->id, 'status' => AttendanceStatus::Present]);

    $response = $this->actingAs($student)->getJson(route('gradebook.sessions', [$course, 'attendance']));

    $response->assertOk()->assertJsonCount(1, 'sessions')
        ->assertJsonFragment(['weight' => 10.0, 'score' => 100.0]);
});

test('a type with no sessions in scope returns an empty list', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $school->id]));
    $student->givePermissionTo('gradebook.view');
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);

    $response = $this->actingAs($student)->getJson(route('gradebook.sessions', [$course, 'attendance']));

    $response->assertOk()->assertJsonCount(0, 'sessions');
});

test('student cannot fetch another students breakdown via student_id', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $school->id]));
    $other = User::factory()->forSchool($school)->create();
    $student->givePermissionTo('gradebook.view');
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $other->id]);

    Assessment::factory()->for($course)->create(['type' => 'attendance', 'weight' => 10, 'start_date' => null, 'end_date' => null]);
    $session = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $other->id, 'status' => AttendanceStatus::Present]);

    $response = $this->actingAs($student)->getJson(route('gradebook.sessions', [$course, 'attendance']).'?student_id='.$other->id);

    // student_id is ignored for a Student caller — the session is still in scope
    // (it exists course-wide), but the score reflects the caller's own attendance, not the other student's.
    $response->assertOk()->assertJsonCount(1, 'sessions')
        ->assertJsonFragment(['weight' => 10.0, 'score' => 0.0]);
});

test('teacher can fetch a specific students breakdown via student_id', function () {
    $school = School::factory()->create();
    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole(Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $school->id]));
    $teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
    $student = User::factory()->forSchool($school)->create();
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);

    Assessment::factory()->for($course)->create(['type' => 'attendance', 'weight' => 10, 'start_date' => null, 'end_date' => null]);
    $session = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $student->id, 'status' => AttendanceStatus::Present]);

    $response = $this->actingAs($teacher)->getJson(route('gradebook.sessions', [$course, 'attendance']).'?student_id='.$student->id);

    $response->assertOk()->assertJsonCount(1, 'sessions');
});

test('an unknown assessment type returns 404', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $school->id]));
    $student->givePermissionTo('gradebook.view');
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);

    $response = $this->actingAs($student)->getJson(route('gradebook.sessions', [$course, 'not-a-real-type']));

    $response->assertNotFound();
});

test('user without gradebook.view permission is forbidden', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $school->id]));
    $course = Course::factory()->for($school)->create();
    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);

    $response = $this->actingAs($student)->getJson(route('gradebook.sessions', [$course, 'attendance']));

    $response->assertForbidden();
});
