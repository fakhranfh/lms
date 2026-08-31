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
