<?php

use App\Enums\AttendanceRequirementType;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceRequirement;
use App\Models\Course;
use App\Models\CourseAttendanceSetting;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\Session;
use App\Models\User;
use App\Models\VideoConference;
use App\Models\VideoConferenceParticipation;
use App\Services\AttendanceDerivationService;
use Illuminate\Database\Eloquent\Collection;

test('manual_checkin is fulfilled only when the attendance status is present', function () {
    $session = Session::factory()->create();
    $user = User::factory()->create();

    $requirement = AttendanceRequirement::factory()->create([
        'course_id' => $session->course_id,
        'requirement_type' => AttendanceRequirementType::ManualCheckin,
    ]);

    $service = app(AttendanceDerivationService::class);

    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeFalse();

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Late]);
    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeFalse();

    Attendance::where('session_id', $session->id)->where('user_id', $user->id)->update(['status' => AttendanceStatus::Present]);
    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeTrue();
});

test('forum_completed is fulfilled once the session post threshold is met', function () {
    $session = Session::factory()->create(['required_forum_posts' => 2]);
    $forum = Forum::factory()->create(['course_id' => $session->course_id, 'session_id' => $session->id]);
    $thread = ForumThread::factory()->create(['forum_id' => $forum->id]);
    $user = User::factory()->create();

    $requirement = AttendanceRequirement::factory()->create([
        'course_id' => $session->course_id,
        'requirement_type' => AttendanceRequirementType::ForumCompleted,
    ]);

    $service = app(AttendanceDerivationService::class);

    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);
    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeFalse();

    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);
    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeTrue();
});

test('class_duration_completed is fulfilled once participation meets the required minutes', function () {
    $session = Session::factory()->create();
    $conference = VideoConference::factory()->create(['session_id' => $session->id, 'required_duration_minutes' => 60]);
    $user = User::factory()->create();

    $requirement = AttendanceRequirement::factory()->create([
        'course_id' => $session->course_id,
        'requirement_type' => AttendanceRequirementType::ClassDurationCompleted,
    ]);

    $service = app(AttendanceDerivationService::class);

    VideoConferenceParticipation::factory()->create([
        'video_conference_id' => $conference->id,
        'user_id' => $user->id,
        'joined_at' => now(),
        'left_at' => now()->addMinutes(30),
    ]);
    $session->load('videoConferences.participations');
    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeFalse();

    VideoConferenceParticipation::factory()->create([
        'video_conference_id' => $conference->id,
        'user_id' => $user->id,
        'joined_at' => now(),
        'left_at' => now()->addMinutes(60),
    ]);
    $session->load('videoConferences.participations');
    expect($service->isRequirementFulfilled($requirement, $session, $user->id))->toBeTrue();
});

test('a session with no configured requirements falls back to a manual attendance record', function () {
    $session = Session::factory()->create();
    $user = User::factory()->create();

    $service = app(AttendanceDerivationService::class);

    expect($service->isSessionAttended($session, $user->id, new Collection))->toBeFalse();

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);
    expect($service->isSessionAttended($session, $user->id, new Collection))->toBeTrue();
});

test('summary for student counts total sessions, attended sessions and minimal attendance', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();
    CourseAttendanceSetting::factory()->create(['course_id' => $course->id, 'minimal_attendance' => 5]);

    $attendedSession = Session::factory()->create(['course_id' => $course->id]);
    Attendance::factory()->create(['session_id' => $attendedSession->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    Session::factory()->create(['course_id' => $course->id]);

    $summary = app(AttendanceDerivationService::class)->summaryForStudent($course, $user->id);

    expect($summary)->toBe([
        'total_session' => 2,
        'total_attendance' => 1,
        'minimal_attendance' => 5,
    ]);
});
