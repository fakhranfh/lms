<?php

use App\Models\Course;
use App\Models\School;
use App\Models\User;

test('course can be created and associated with school', function () {
    $school = School::factory()->create();
    $user = User::factory()->forSchool($school)->create();

    $course = Course::factory()
        ->for($school)
        ->for($user, 'creator')
        ->create();

    expect($course->school_id)->toBe($school->id);
    expect($course->created_by)->toBe($user->id);
    expect($course->title)->toBeString();
});

test('course slug is unique per school', function () {
    $school1 = School::factory()->create();
    $school2 = School::factory()->create();

    $course1 = Course::factory()
        ->for($school1)
        ->create(['slug' => 'math-101']);

    $course2 = Course::factory()
        ->for($school2)
        ->create(['slug' => 'math-101']);

    expect($course1->slug)->toBe('math-101');
    expect($course2->slug)->toBe('math-101');
});
