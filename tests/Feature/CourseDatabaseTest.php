<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
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

test('module belongs to course with order', function () {
    $course = Course::factory()->create();

    $module = Module::factory()
        ->for($course)
        ->create(['order' => 1]);

    expect($module->course_id)->toBe($course->id);
    expect($module->order)->toBe(1);
});

test('lesson belongs to module with order', function () {
    $course = Course::factory()->create();
    $module = Module::factory()->for($course)->create(['order' => 1]);

    $lesson = Lesson::factory()
        ->for($module)
        ->create(['order' => 1]);

    expect($lesson->module_id)->toBe($module->id);
    expect($lesson->order)->toBe(1);
});

test('lesson can track user completion', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $lesson->markCompleteFor($user);

    expect($lesson->isCompletedBy($user))->toBeTrue();
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

test('module and lesson ordering is enforced', function () {
    $course = Course::factory()->create();

    $module1 = Module::factory()->for($course)->create(['order' => 1]);
    $module2 = Module::factory()->for($course)->create(['order' => 2]);

    expect($course->modules()->count())->toBe(2);
    expect($course->modules()->first()->order)->toBe(1);
});

test('cascade delete removes related records', function () {
    $course = Course::factory()->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();

    $courseId = $course->id;
    $course->delete();

    expect(Course::find($courseId))->toBeNull();
    expect(Module::find($module->id))->toBeNull();
    expect(Lesson::find($lesson->id))->toBeNull();
});
