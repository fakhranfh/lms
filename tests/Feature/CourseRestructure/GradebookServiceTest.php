<?php

use App\Models\Course;
use App\Models\GradebookEntry;
use App\Models\GradebookGradeScale;
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

test('grade scale is ordered and scoped to a course', function () {
    $course = Course::factory()->create();
    GradebookGradeScale::factory()->for($course)->create(['label' => 'B', 'order' => 2, 'score_min' => 75, 'score_max' => 84]);
    GradebookGradeScale::factory()->for($course)->create(['label' => 'A', 'order' => 1, 'score_min' => 85, 'score_max' => 100]);

    $scales = GradebookGradeScale::where('course_id', $course->id)->orderBy('order')->get();

    expect($scales->pluck('label')->all())->toBe(['A', 'B']);
});

test('force deleting a course cascades to gradebook entries', function () {
    $course = Course::factory()->create();
    $entry = GradebookEntry::factory()->for($course)->create();

    $course->forceDelete();

    expect(GradebookEntry::find($entry->id))->toBeNull();
});
