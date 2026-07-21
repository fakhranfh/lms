<?php

use App\Models\Course;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Database\Seeders\ContentEngineSeeder;

test('content engine seeder creates courses for all schools', function () {
    $school1 = School::factory()->create();
    $school2 = School::factory()->create();

    User::factory()->for($school1)->create();
    User::factory()->for($school2)->create();

    $this->seed(ContentEngineSeeder::class);

    expect(Course::where('school_id', $school1->id)->count())->toBeGreaterThan(0);
    expect(Course::where('school_id', $school2->id)->count())->toBeGreaterThan(0);
});

test('content engine seeder creates courses with modules', function () {
    $school = School::factory()->create();
    User::factory()->for($school)->create();

    $this->seed(ContentEngineSeeder::class);

    $courses = Course::where('school_id', $school->id)->get();
    expect($courses->count())->toBeGreaterThan(0);

    foreach ($courses as $course) {
        expect($course->modules()->count())->toBeGreaterThan(0);
    }
});

test('content engine seeder creates modules with lessons', function () {
    $school = School::factory()->create();
    User::factory()->for($school)->create();

    $this->seed(ContentEngineSeeder::class);

    $modules = Module::whereIn('course_id', Course::where('school_id', $school->id)->pluck('id'))->get();
    expect($modules->count())->toBeGreaterThan(0);

    foreach ($modules as $module) {
        expect($module->lessons()->count())->toBeGreaterThan(0);
    }
});

test('seeded courses have correct structure', function () {
    $school = School::factory()->create();
    $instructor = User::factory()->for($school)->create();

    $this->seed(ContentEngineSeeder::class);

    $courses = Course::where('school_id', $school->id)->get();

    foreach ($courses as $course) {
        expect($course->title)->toBeString()->not->toBeEmpty();
        expect($course->slug)->toBeString()->not->toBeEmpty();
        expect($course->description)->toBeString();
        expect($course->created_by)->toBeString();
        expect($course->is_published)->toBeBool();

        $modules = $course->modules()->orderBy('order')->get();
        expect($modules->count())->toBeGreaterThan(0);

        foreach ($modules as $index => $module) {
            expect($module->order)->toBe($index + 1);
            expect($module->title)->toBeString()->not->toBeEmpty();
            expect($module->is_published)->toBeBool();

            $lessons = $module->lessons()->orderBy('order')->get();
            expect($lessons->count())->toBeGreaterThan(0);

            foreach ($lessons as $lessonIndex => $lesson) {
                expect($lesson->order)->toBe($lessonIndex + 1);
                expect($lesson->title)->toBeString()->not->toBeEmpty();
                expect($lesson->content)->toBeString();
                expect($lesson->duration_minutes)->toBeInt();
                expect($lesson->is_published)->toBe($course->is_published);
            }
        }
    }
});

test('seeded courses have published and unpublished variants', function () {
    $school = School::factory()->create();
    User::factory()->for($school)->create();

    $this->seed(ContentEngineSeeder::class);

    $courses = Course::where('school_id', $school->id)->get();

    $publishedCourses = $courses->where('is_published', true)->count();
    $unpublishedCourses = $courses->where('is_published', false)->count();

    expect($publishedCourses)->toBeGreaterThan(0);
    expect($unpublishedCourses)->toBeGreaterThan(0);
});

test('seeded content belongs to correct school', function () {
    $school = School::factory()->create();
    User::factory()->for($school)->create();

    $this->seed(ContentEngineSeeder::class);

    $courses = Course::where('school_id', $school->id)->get();

    foreach ($courses as $course) {
        expect($course->school_id)->toBe($school->id);

        foreach ($course->modules as $module) {
            expect($module->course_id)->toBe($course->id);

            foreach ($module->lessons as $lesson) {
                expect($lesson->module_id)->toBe($module->id);
            }
        }
    }
});

test('seeded courses are created by school instructors', function () {
    $school = School::factory()->create();
    $instructor = User::factory()->for($school)->create();

    $this->seed(ContentEngineSeeder::class);

    $courses = Course::where('school_id', $school->id)->get();

    foreach ($courses as $course) {
        $creator = User::find($course->created_by);
        expect($creator)->not->toBeNull();
        expect($creator->school_id)->toBe($school->id);
    }
});
