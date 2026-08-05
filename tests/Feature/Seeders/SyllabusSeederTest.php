<?php

use App\Models\Course;
use App\Models\School;
use App\Models\Syllabus;
use Database\Seeders\SyllabusSeeder;

test('syllabus seeder creates a syllabus with policies, learning outcomes, evaluations, and rubric for a course', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();

    (new SyllabusSeeder)->run();

    $syllabus = Syllabus::where('course_id', $course->id)->first();

    expect($syllabus)->not->toBeNull();
    expect($syllabus->classPolicies()->count())->toBe(7);
    expect($syllabus->learningOutcomes()->count())->toBe(4);

    $evaluation = $syllabus->evaluations()->first();
    expect($evaluation)->not->toBeNull();

    $weightSum = $evaluation->activities()->sum('weight');
    expect((float) $weightSum)->toBe(100.0);

    $firstActivity = $evaluation->activities()->first();
    expect($firstActivity->learningOutcomes()->count())->toBe(4);

    expect($syllabus->rubricProficiencyLevels()->count())->toBe(4);

    $firstOutcome = $syllabus->learningOutcomes()->first();
    expect($firstOutcome->rubricKeyIndicators()->count())->toBe(2);

    $firstKeyIndicator = $firstOutcome->rubricKeyIndicators()->first();
    expect($firstKeyIndicator->cells()->count())->toBe(4);
});

test('syllabus seeder skips courses that already have a syllabus', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    Syllabus::factory()->for($course)->create();

    (new SyllabusSeeder)->run();

    expect(Syllabus::where('course_id', $course->id)->count())->toBe(1);
});
