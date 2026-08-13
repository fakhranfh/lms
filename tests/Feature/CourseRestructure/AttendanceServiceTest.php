<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseAttendanceSetting;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceService;

test('attendance can be found by session and user', function () {
    $session = Session::factory()->create();
    $user = User::factory()->create();
    $attendance = Attendance::factory()->for($session)->for($user)->create(['status' => AttendanceStatus::Present]);

    $found = app(AttendanceService::class)->findBySessionAndUser($session->id, $user->id);

    expect($found->id)->toBe($attendance->id);
    expect($found->status)->toBe(AttendanceStatus::Present);
});

test('course attendance setting stores minimal attendance', function () {
    $course = Course::factory()->create();
    CourseAttendanceSetting::factory()->for($course)->create(['minimal_attendance' => 12]);

    expect(CourseAttendanceSetting::where('course_id', $course->id)->first()->minimal_attendance)->toBe(12);
});

test('force deleting a course cascades to attendance records', function () {
    $session = Session::factory()->create();
    $attendance = Attendance::factory()->for($session)->create();
    $course = $session->course;

    $course->forceDelete();

    expect(Attendance::find($attendance->id))->toBeNull();
});
