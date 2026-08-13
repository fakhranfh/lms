<?php

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseAttendanceSetting;
use App\Models\Session;
use App\Models\User;
use App\Models\VideoConference;
use App\Models\VideoConferenceParticipation;
use App\Services\AttendanceDerivationService;

test('online sessions are not applicable for attendance', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Online]);

    $service = app(AttendanceDerivationService::class);

    expect($service->isAttendanceApplicable($session))->toBeFalse();
});

test('offline and virtual_class sessions are applicable for attendance', function () {
    $service = app(AttendanceDerivationService::class);

    expect($service->isAttendanceApplicable(Session::factory()->create(['delivery_mode' => DeliveryMode::Offline])))->toBeTrue()
        ->and($service->isAttendanceApplicable(Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass])))->toBeTrue();
});

test('virtual_class session is attended when the student has any video conference join record', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    $conference = VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();

    $service = app(AttendanceDerivationService::class);

    expect($service->isSessionAttended($session, $user->id))->toBeFalse();

    VideoConferenceParticipation::factory()->create([
        'video_conference_id' => $conference->id,
        'user_id' => $user->id,
        'joined_at' => now(),
        'left_at' => null,
    ]);
    $session->load('videoConferences.participations');

    expect($service->isSessionAttended($session, $user->id))->toBeTrue()
        ->and($service->attendanceSourceForSession($session, $user->id))->toBe('video_conference');
});

test('virtual_class session is also attended when a Teacher manually marks the student present', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();

    $service = app(AttendanceDerivationService::class);

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);
    $session->load('videoConferences.participations');

    expect($service->isSessionAttended($session, $user->id))->toBeTrue()
        ->and($service->attendanceSourceForSession($session, $user->id))->toBe('manual');
});

test('offline session is only attended via a manual present mark, never auto-derived', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Offline]);
    $user = User::factory()->create();

    $service = app(AttendanceDerivationService::class);

    expect($service->isSessionAttended($session, $user->id))->toBeFalse();

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Late]);
    expect($service->isSessionAttended($session, $user->id))->toBeFalse();

    Attendance::where('session_id', $session->id)->where('user_id', $user->id)->update(['status' => AttendanceStatus::Present]);
    expect($service->isSessionAttended($session, $user->id))->toBeTrue()
        ->and($service->attendanceSourceForSession($session, $user->id))->toBe('manual');
});

test('summary for student excludes online sessions from totals', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();
    CourseAttendanceSetting::factory()->create(['course_id' => $course->id, 'minimal_attendance' => 5]);

    $attendedOffline = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Offline]);
    Attendance::factory()->create(['session_id' => $attendedOffline->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Online]);

    $summary = app(AttendanceDerivationService::class)->summaryForStudent($course, $user->id);

    expect($summary)->toBe([
        'total_session' => 2,
        'total_attendance' => 1,
        'minimal_attendance' => 5,
    ]);
});
