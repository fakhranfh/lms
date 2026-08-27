<?php

use App\Models\Course;
use App\Models\GradebookEntry;
use App\Models\GradebookSessionEntry;
use App\Models\Session;
use App\Models\User;
use App\Services\GradebookEntryService;

test('gradebook entry can be found for a course and user, with session breakdown', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();
    $entry = GradebookEntry::factory()->for($course)->for($user)->create();
    $session = Session::factory()->for($course)->create();
    GradebookSessionEntry::factory()->for($entry, 'gradebookEntry')->for($session)->create();

    $entries = app(GradebookEntryService::class)->forCourseAndUser($course->id, $user->id);

    expect($entries)->toHaveCount(1);
    expect($entries->first()->sessionEntries()->count())->toBe(1);
});

test('force deleting a course cascades to gradebook entries', function () {
    $course = Course::factory()->create();
    $entry = GradebookEntry::factory()->for($course)->create();

    $course->forceDelete();

    expect(GradebookEntry::find($entry->id))->toBeNull();
});
