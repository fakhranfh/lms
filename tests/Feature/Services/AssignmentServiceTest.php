<?php

use App\Models\Assignment;
use App\Models\Lesson;
use App\Services\AssignmentService;

test('service can be instantiated', function () {
    $service = app(AssignmentService::class);

    expect($service)->toBeInstanceOf(AssignmentService::class);
});

test('can create an assignment', function () {
    $lesson = Lesson::factory()->create();
    $service = app(AssignmentService::class);

    $assignment = $service->create([
        'lesson_id' => $lesson->id,
        'title' => 'Essay Assignment',
        'prompt_question' => 'Explain X.',
        'max_score' => 100,
    ]);

    expect($assignment->lesson_id)->toBe($lesson->id);
});

test('can get assignments for a lesson', function () {
    $lesson = Lesson::factory()->create();
    Assignment::factory()->for($lesson)->count(2)->create();
    Assignment::factory()->create();

    $service = app(AssignmentService::class);

    expect($service->getByLesson($lesson->id))->toHaveCount(2);
});

test('can publish and unpublish an assignment', function () {
    $assignment = Assignment::factory()->create(['is_published' => false]);
    $service = app(AssignmentService::class);

    $published = $service->publish($assignment->id);
    expect($published->is_published)->toBeTrue();

    $unpublished = $service->unpublish($assignment->id);
    expect($unpublished->is_published)->toBeFalse();
});

test('can delete an assignment', function () {
    $assignment = Assignment::factory()->create();
    $service = app(AssignmentService::class);

    $service->delete($assignment->id);

    expect(Assignment::find($assignment->id))->toBeNull();
});
