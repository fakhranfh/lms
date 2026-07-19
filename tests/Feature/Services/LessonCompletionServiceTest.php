<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\User;
use App\Services\LessonCompletionService;

describe('LessonCompletionService', function () {
    describe('service initialization', function () {
        test('service can be instantiated', function () {
            $service = app(LessonCompletionService::class);
            expect($service)->toBeInstanceOf(LessonCompletionService::class);
        });

        test('service has required methods', function () {
            $service = app(LessonCompletionService::class);

            foreach (['isLessonComplete', 'getLessonProgress', 'markLessonIfComplete', 'getModuleProgress', 'getCourseProgress'] as $method) {
                expect(method_exists($service, $method))->toBeTrue();
            }
        });
    });

    describe('lesson completion logic', function () {
        test('lesson with no materials is not complete', function () {
            $lesson = Lesson::factory()->create();
            $user = User::factory()->create();

            $service = app(LessonCompletionService::class);
            $isComplete = $service->isLessonComplete($lesson, $user);

            expect($isComplete)->toBeFalse();
        });

        test('lesson progress returns correct structure', function () {
            $lesson = Lesson::factory()->create();
            $user = User::factory()->create();

            $service = app(LessonCompletionService::class);
            $progress = $service->getLessonProgress($lesson, $user);

            expect($progress)->toHaveKeys(['total', 'accessed', 'percentage', 'is_complete'])
                ->and($progress['total'])->toBeInt()
                ->and($progress['accessed'])->toBeInt()
                ->and($progress['percentage'])->toBeFloat();
        });

        test('progress zero when lesson has no materials', function () {
            $lesson = Lesson::factory()->create();
            $user = User::factory()->create();

            $service = app(LessonCompletionService::class);
            $progress = $service->getLessonProgress($lesson, $user);

            expect($progress['total'])->toBe(0)
                ->and($progress['accessed'])->toBe(0)
                ->and($progress['percentage'])->toBe(0.0)
                ->and($progress['is_complete'])->toBeFalse();
        });
    });

    describe('module and course progress', function () {
        test('getModuleProgress returns collection of lesson progress', function () {
            $module = Module::factory()->create();
            $lesson1 = Lesson::factory()->create(['module_id' => $module->id]);
            $lesson2 = Lesson::factory()->create(['module_id' => $module->id]);
            $user = User::factory()->create();

            $service = app(LessonCompletionService::class);
            $progress = $service->getModuleProgress($module->id, $user);

            expect($progress)->toHaveCount(2)
                ->and($progress[0])->toHaveKeys(['lesson_id', 'lesson_title', 'progress'])
                ->and($progress[1])->toHaveKeys(['lesson_id', 'lesson_title', 'progress']);
        });

        test('getCourseProgress returns collection of module progress', function () {
            $course = Course::factory()->create();
            $module1 = Module::factory()->create(['course_id' => $course->id]);
            $module2 = Module::factory()->create(['course_id' => $course->id]);
            $lesson1 = Lesson::factory()->create(['module_id' => $module1->id]);
            $lesson2 = Lesson::factory()->create(['module_id' => $module2->id]);
            $user = User::factory()->create();

            $service = app(LessonCompletionService::class);
            $progress = $service->getCourseProgress($course->id, $user);

            expect($progress)->toHaveCount(2)
                ->and($progress[0])->toHaveKeys(['module_id', 'module_title', 'lessons', 'total_lessons', 'completed_lessons', 'progress_percentage'])
                ->and($progress[1])->toHaveKeys(['module_id', 'module_title', 'lessons', 'total_lessons', 'completed_lessons', 'progress_percentage']);
        });

        test('course progress aggregates module data correctly', function () {
            $course = Course::factory()->create();
            $module = Module::factory()->create(['course_id' => $course->id]);
            $lesson1 = Lesson::factory()->create(['module_id' => $module->id]);
            $lesson2 = Lesson::factory()->create(['module_id' => $module->id]);
            $user = User::factory()->create();

            LessonMaterial::factory()->create(['lesson_id' => $lesson1->id]);
            LessonMaterial::factory()->create(['lesson_id' => $lesson2->id]);

            $service = app(LessonCompletionService::class);
            $progress = $service->getCourseProgress($course->id, $user);

            expect($progress[0]['total_lessons'])->toBe(2)
                ->and($progress[0]['completed_lessons'])->toBeInt()
                ->and($progress[0]['progress_percentage'])->toBeFloat();
        });
    });

    describe('completion marking', function () {
        test('markLessonIfComplete does not error on empty lesson', function () {
            $lesson = Lesson::factory()->create();
            $user = User::factory()->create();

            $service = app(LessonCompletionService::class);
            $service->markLessonIfComplete($lesson, $user);

            // Should not throw, just complete without effect
            expect(true)->toBeTrue();
        });

        test('markLessonIfComplete handles missing pivot gracefully', function () {
            $lesson = Lesson::factory()->create();
            $user = User::factory()->create();
            $material = LessonMaterial::factory()->create(['lesson_id' => $lesson->id]);

            $service = app(LessonCompletionService::class);
            // Should handle case where user is not attached
            $service->markLessonIfComplete($lesson, $user);

            expect(true)->toBeTrue();
        });
    });
});
