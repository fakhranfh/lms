<?php

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Models\Attendance;
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

    expect($service->isSessionAttended($session, $user->id))->toBeTrue();
});

test('virtual_class session is also attended when a Teacher manually marks the student present', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();

    $service = app(AttendanceDerivationService::class);

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);
    $session->load('videoConferences.participations');

    expect($service->isSessionAttended($session, $user->id))->toBeTrue();
});

test('virtual_class session is attended when both join record and manual mark exist', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    $conference = VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();

    VideoConferenceParticipation::factory()->create([
        'video_conference_id' => $conference->id,
        'user_id' => $user->id,
        'joined_at' => now(),
        'left_at' => null,
    ]);
    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);
    $session->load('videoConferences.participations');

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeTrue();
});

test('offline session is only attended via a manual present mark, never auto-derived', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Offline]);
    $user = User::factory()->create();

    $service = app(AttendanceDerivationService::class);

    expect($service->isSessionAttended($session, $user->id))->toBeFalse();

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Late]);
    expect($service->isSessionAttended($session, $user->id))->toBeFalse();

    Attendance::where('session_id', $session->id)->where('user_id', $user->id)->update(['status' => AttendanceStatus::Present]);
    expect($service->isSessionAttended($session, $user->id))->toBeTrue();
});

test('online session is not attended when forum post count is below the required threshold', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    $forum = Forum::factory()->create(['course_id' => $session->course_id, 'session_id' => $session->id]);
    $user = User::factory()->create();

    $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => User::factory()->create()->id]);

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeFalse();
});

test('online session is attended when forum post count exactly meets the required threshold', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    $forum = Forum::factory()->create(['course_id' => $session->course_id, 'session_id' => $session->id]);
    $user = User::factory()->create();

    $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeTrue();
});

test('online session is attended when forum post count exceeds the required threshold', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
    $forum = Forum::factory()->create(['course_id' => $session->course_id, 'session_id' => $session->id]);
    $user = User::factory()->create();

    $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
    ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);
    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeTrue();
});

test('online session forum post count sums threads and comments together', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 3]);
    $forum = Forum::factory()->create(['course_id' => $session->course_id, 'session_id' => $session->id]);
    $user = User::factory()->create();

    $thread = ForumThread::factory()->create(['forum_id' => $forum->id, 'user_id' => $user->id]);

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeFalse();

    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeFalse();

    ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $user->id]);

    expect(app(AttendanceDerivationService::class)->isSessionAttended($session, $user->id))->toBeTrue();
});

test('self attended at returns null when the student never joined the video conference', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();
    $session->load('videoConferences.participations');

    expect(app(AttendanceDerivationService::class)->selfAttendedAt($session, $user->id))->toBeNull();
});

test('self attended at returns the join datetime when the student joined the video conference', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    $conference = VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();
    $joinedAt = now()->subMinutes(10);

    VideoConferenceParticipation::factory()->create([
        'video_conference_id' => $conference->id,
        'user_id' => $user->id,
        'joined_at' => $joinedAt,
        'left_at' => null,
    ]);
    $session->load('videoConferences.participations');

    $result = app(AttendanceDerivationService::class)->selfAttendedAt($session, $user->id);

    expect($result)->not->toBeNull();
    expect($result->timestamp)->toBe($joinedAt->timestamp);
});

test('self attended at returns null when only a teacher manually marked the student present', function () {
    $session = Session::factory()->create(['delivery_mode' => DeliveryMode::VirtualClass]);
    VideoConference::factory()->create(['session_id' => $session->id]);
    $user = User::factory()->create();

    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);
    $session->load('videoConferences.participations');

    expect(app(AttendanceDerivationService::class)->selfAttendedAt($session, $user->id))->toBeNull();
});

test('summary for student counts sessions across all delivery modes', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();
    CourseAttendanceSetting::factory()->create(['course_id' => $course->id, 'minimal_attendance' => 5]);

    $attendedOffline = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Offline]);
    Attendance::factory()->create(['session_id' => $attendedOffline->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);

    $summary = app(AttendanceDerivationService::class)->summaryForStudent($course, $user->id);

    expect($summary)->toBe([
        'total_session' => 3,
        'total_attendance' => 1,
        'minimal_attendance' => 5,
    ]);
});
