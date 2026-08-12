<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\School;
use App\Models\Session;
use Database\Seeders\SessionAssessmentSeeder;

test('session assessment seeder links unlinked assessments to a course session', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $session = Session::factory()->for($course)->create();
    $assessment = Assessment::factory()->for($course)->create(['session_id' => null]);

    (new SessionAssessmentSeeder)->run();

    expect($assessment->fresh()->session_id)->toBe($session->id);
});

test('session assessment seeder spreads assessments across multiple sessions round robin', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $sessionOne = Session::factory()->for($course)->create(['date_start' => now()->subDays(2)]);
    $sessionTwo = Session::factory()->for($course)->create(['date_start' => now()->subDay()]);
    $assessmentOne = Assessment::factory()->for($course)->create(['session_id' => null]);
    $assessmentTwo = Assessment::factory()->for($course)->create(['session_id' => null]);
    $assessmentThree = Assessment::factory()->for($course)->create(['session_id' => null]);

    (new SessionAssessmentSeeder)->run();

    expect($assessmentOne->fresh()->session_id)->toBe($sessionOne->id)
        ->and($assessmentTwo->fresh()->session_id)->toBe($sessionTwo->id)
        ->and($assessmentThree->fresh()->session_id)->toBe($sessionOne->id);
});

test('session assessment seeder allows a session to hold many assessments', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $session = Session::factory()->for($course)->create();
    Assessment::factory()->for($course)->count(3)->create(['session_id' => null]);

    (new SessionAssessmentSeeder)->run();

    expect($session->assessments()->count())->toBe(3);
});

test('session assessment seeder does not touch already-linked assessments', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $originalSession = Session::factory()->for($course)->create();
    $otherSession = Session::factory()->for($course)->create();
    $assessment = Assessment::factory()->for($course)->create(['session_id' => $originalSession->id]);

    (new SessionAssessmentSeeder)->run();

    expect($assessment->fresh()->session_id)->toBe($originalSession->id);
});

test('session assessment seeder skips courses without sessions', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $assessment = Assessment::factory()->for($course)->create(['session_id' => null]);

    (new SessionAssessmentSeeder)->run();

    expect($assessment->fresh()->session_id)->toBeNull();
});
