<?php

use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Models\Session;
use Database\Seeders\SessionSeeder;

test('session seeder creates sessions with subtopics, video conferences, and materials for a course', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();

    (new SessionSeeder)->run();

    $sessions = Session::where('course_id', $course->id)->get();

    expect($sessions)->toHaveCount(6);

    $firstSession = $sessions->sortBy('date_start')->first();

    expect($firstSession->subtopics()->count())->toBeGreaterThan(0);
    expect($firstSession->materials()->count())->toBeGreaterThan(0);

    $virtualClassSessions = $sessions->filter(fn ($session) => $session->delivery_mode->value === 'virtual_class');
    expect($virtualClassSessions)->not->toBeEmpty();
    expect($virtualClassSessions->every(fn ($session) => $session->videoConferences()->count() > 0))->toBeTrue();
});

test('session seeder skips courses that already have sessions', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    Session::factory()->for($course)->create();

    (new SessionSeeder)->run();

    expect(Session::where('course_id', $course->id)->count())->toBe(1);
});

test('session seeder reuses existing media library items instead of creating new ones', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    MediaLibraryItem::factory()->for($school)->count(2)->create();

    (new SessionSeeder)->run();

    expect(MediaLibraryItem::where('school_id', $school->id)->count())->toBe(2);
});
