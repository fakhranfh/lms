<?php

use App\Models\Course;
use App\Models\Syllabus;
use App\Models\SyllabusClassPolicy;
use App\Models\SyllabusEvaluation;
use App\Models\SyllabusEvaluationActivity;
use App\Models\SyllabusLearningOutcome;
use App\Models\SyllabusRubricCell;
use App\Models\SyllabusRubricKeyIndicator;
use App\Models\SyllabusRubricProficiencyLevel;
use App\Services\SyllabusEvaluationActivityService;
use App\Services\SyllabusService;

test('syllabus service finds a syllabus by course', function () {
    $course = Course::factory()->create();
    $syllabus = Syllabus::factory()->for($course)->create();

    expect(app(SyllabusService::class)->findByCourse($course->id)->id)->toBe($syllabus->id);
});

test('syllabus has class policies scoped by delivery mode', function () {
    $syllabus = Syllabus::factory()->create();
    SyllabusClassPolicy::factory()->for($syllabus)->create(['scope' => 'online']);
    SyllabusClassPolicy::factory()->for($syllabus)->create(['scope' => 'general']);

    expect($syllabus->classPolicies()->count())->toBe(2);
});

test('evaluation activity maps to learning outcomes', function () {
    $syllabus = Syllabus::factory()->create();
    $lo1 = SyllabusLearningOutcome::factory()->for($syllabus)->create(['code' => 'LO1']);
    $lo2 = SyllabusLearningOutcome::factory()->for($syllabus)->create(['code' => 'LO2']);
    $evaluation = SyllabusEvaluation::factory()->for($syllabus)->create();
    $activity = SyllabusEvaluationActivity::factory()->for($evaluation, 'evaluation')->create();

    app(SyllabusEvaluationActivityService::class)->syncLearningOutcomes($activity->id, [$lo1->id, $lo2->id]);

    expect($activity->learningOutcomes()->count())->toBe(2);
});

test('rubric cell links key indicator and proficiency level', function () {
    $syllabus = Syllabus::factory()->create();
    $lo = SyllabusLearningOutcome::factory()->for($syllabus)->create();
    $indicator = SyllabusRubricKeyIndicator::factory()->for($lo, 'learningOutcome')->create();
    $level = SyllabusRubricProficiencyLevel::factory()->for($syllabus)->create();

    $cell = SyllabusRubricCell::factory()
        ->for($indicator, 'keyIndicator')
        ->for($level, 'proficiencyLevel')
        ->create();

    expect($cell->keyIndicator->id)->toBe($indicator->id);
    expect($cell->proficiencyLevel->id)->toBe($level->id);
});

test('force deleting a course cascades to its syllabus', function () {
    $course = Course::factory()->create();
    $syllabus = Syllabus::factory()->for($course)->create();

    $course->forceDelete();

    expect(Syllabus::find($syllabus->id))->toBeNull();
});
