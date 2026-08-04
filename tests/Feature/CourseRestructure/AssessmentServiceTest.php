<?php

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Group;
use App\Models\User;
use App\Services\AssessmentAttemptService;

test('assessment has questions and a default weight per type', function () {
    $assessment = Assessment::factory()->create([
        'type' => 'theory_personal_assignment',
        'weight' => AssessmentType::TheoryPersonalAssignment->defaultWeight(),
    ]);
    AssessmentQuestion::factory()->for($assessment)->create(['order' => 1]);

    expect($assessment->questions()->count())->toBe(1);
    expect((float) $assessment->weight)->toBe(20.0);
});

test('personal assignment attempt is tied to a user', function () {
    $assessment = Assessment::factory()->create(['assigned_to' => AssessmentAssignedTo::Individual]);
    $user = User::factory()->create();

    $attempt = app(AssessmentAttemptService::class)->create([
        'assessment_id' => $assessment->id,
        'user_id' => $user->id,
        'attempt_number' => 1,
    ]);

    expect(app(AssessmentAttemptService::class)->forAssessmentAndUser($assessment->id, $user->id))->toHaveCount(1);
    expect($attempt->group_id)->toBeNull();
});

test('team assignment attempt is tied to a group with submitted_by tracked', function () {
    $assessment = Assessment::factory()->create(['assigned_to' => AssessmentAssignedTo::Group]);
    $group = Group::factory()->create();
    $submitter = User::factory()->create();

    $attempt = app(AssessmentAttemptService::class)->create([
        'assessment_id' => $assessment->id,
        'group_id' => $group->id,
        'submitted_by' => $submitter->id,
        'attempt_number' => 1,
    ]);

    expect($attempt->user_id)->toBeNull();
    expect($attempt->submitter->id)->toBe($submitter->id);
});

test('an attempt has one answer and one score', function () {
    $attempt = AssessmentAttempt::factory()->create();
    AssessmentAnswer::factory()->for($attempt, 'attempt')->create();
    AssessmentScore::factory()->for($attempt, 'attempt')->create();

    expect($attempt->answer)->not->toBeNull();
    expect($attempt->score)->not->toBeNull();
});

test('force deleting a course cascades to assessments and attempts', function () {
    $assessment = Assessment::factory()->create();
    $attempt = AssessmentAttempt::factory()->for($assessment)->create();
    $course = $assessment->course;

    $course->forceDelete();

    expect(Assessment::find($assessment->id))->toBeNull();
    expect(AssessmentAttempt::find($attempt->id))->toBeNull();
});
