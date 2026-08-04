<?php

use App\Models\Course;

test('course published_at is set when created as published', function () {
    $course = Course::factory()->create(['is_published' => true]);

    expect($course->published_at)->not->toBeNull();
});

test('course published_at stays null when created as draft', function () {
    $course = Course::factory()->create(['is_published' => false]);

    expect($course->published_at)->toBeNull();
});

test('course published_at is set when publishing later', function () {
    $course = Course::factory()->create(['is_published' => false]);

    expect($course->published_at)->toBeNull();

    $course->update(['is_published' => true]);

    expect($course->fresh()->published_at)->not->toBeNull();
});

test('course published_at is cleared when unpublishing', function () {
    $course = Course::factory()->create(['is_published' => true]);

    expect($course->published_at)->not->toBeNull();

    $course->update(['is_published' => false]);

    expect($course->fresh()->published_at)->toBeNull();
});

test('unrelated update does not touch published_at', function () {
    $course = Course::factory()->create(['is_published' => true]);
    $originalPublishedAt = $course->published_at;

    $course->update(['title' => 'Updated Title']);

    expect($course->fresh()->published_at->equalTo($originalPublishedAt))->toBeTrue();
});
