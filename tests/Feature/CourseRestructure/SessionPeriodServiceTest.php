<?php

use App\Models\Course;
use App\Models\Period;
use App\Models\Session;
use App\Models\SessionSubtopic;
use App\Models\VideoConference;
use App\Models\VideoConferenceParticipation;
use App\Services\PeriodService;
use App\Services\SessionService;

test('session service creates and finds a session for a course', function () {
    $course = Course::factory()->create();
    $service = app(SessionService::class);

    $session = $service->create([
        'course_id' => $course->id,
        'title' => 'Session 1',
        'learning_outcome' => 'Understand basics',
        'date_start' => now(),
        'date_end' => now()->addWeek(),
        'delivery_mode' => 'online',
    ]);

    expect($session->course_id)->toBe($course->id);
    expect($service->find($session->id)->title)->toBe('Session 1');
    expect($service->forCourse($course->id))->toHaveCount(1);
});

test('session has subtopics and video conferences', function () {
    $session = Session::factory()->create();
    SessionSubtopic::factory()->for($session)->create(['order' => 1]);
    VideoConference::factory()->for($session)->create();

    expect($session->subtopics()->count())->toBe(1);
    expect($session->videoConferences()->count())->toBe(1);
});

test('video conference tracks participation', function () {
    $videoConference = VideoConference::factory()->create();
    VideoConferenceParticipation::factory()->for($videoConference)->create();

    expect($videoConference->participations()->count())->toBe(1);
});

test('period groups multiple sessions', function () {
    $course = Course::factory()->create();
    $period = Period::factory()->for($course)->create();
    $session1 = Session::factory()->for($course)->create();
    $session2 = Session::factory()->for($course)->create();

    $period->sessions()->attach([$session1->id => ['order' => 1], $session2->id => ['order' => 2]]);

    expect(app(PeriodService::class)->find($period->id, ['sessions'])->sessions)->toHaveCount(2);
});

test('force deleting a course cascades to sessions and their children', function () {
    $course = Course::factory()->create();
    $session = Session::factory()->for($course)->create();
    $subtopic = SessionSubtopic::factory()->for($session)->create();

    $course->forceDelete();

    expect(Session::find($session->id))->toBeNull();
    expect(SessionSubtopic::find($subtopic->id))->toBeNull();
});
