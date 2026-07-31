<?php

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Validation\ValidationException;

test('service can be instantiated', function () {
    $service = app(SubmissionService::class);

    expect($service)->toBeInstanceOf(SubmissionService::class);
});

test('can submit an answer for a published assignment', function () {
    $assignment = Assignment::factory()->create(['is_published' => true]);
    $user = User::factory()->create();

    $service = app(SubmissionService::class);

    $submission = $service->submit([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'student_answer' => 'My answer.',
    ]);

    expect($submission->status)->toBe(SubmissionStatus::Pending);
});

test('cannot submit to an unpublished assignment', function () {
    $assignment = Assignment::factory()->create(['is_published' => false]);
    $user = User::factory()->create();

    $service = app(SubmissionService::class);

    expect(fn () => $service->submit([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'student_answer' => 'My answer.',
    ]))->toThrow(ValidationException::class);
});

test('cannot resubmit when assignment disallows multiple submissions and a graded submission exists', function () {
    $assignment = Assignment::factory()->create([
        'is_published' => true,
        'allow_multiple_submissions' => false,
    ]);
    $user = User::factory()->create();

    Submission::factory()->for($assignment)->for($user)->create([
        'status' => SubmissionStatus::Graded,
    ]);

    $service = app(SubmissionService::class);

    expect(fn () => $service->submit([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'student_answer' => 'Another answer.',
    ]))->toThrow(ValidationException::class);
});

test('can resubmit when assignment allows multiple submissions', function () {
    $assignment = Assignment::factory()->create([
        'is_published' => true,
        'allow_multiple_submissions' => true,
    ]);
    $user = User::factory()->create();

    Submission::factory()->for($assignment)->for($user)->create([
        'status' => SubmissionStatus::Graded,
    ]);

    $service = app(SubmissionService::class);

    $submission = $service->submit([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'student_answer' => 'Another answer.',
    ]);

    expect($submission->status)->toBe(SubmissionStatus::Pending);
});

test('can override a submission score', function () {
    $submission = Submission::factory()->create(['ai_score' => 50]);
    $teacher = User::factory()->create();

    $service = app(SubmissionService::class);
    $updated = $service->overrideScore($submission->id, 88, 'Nicely done', $teacher);

    expect((float) $updated->teacher_score)->toBe(88.0);
    expect($updated->reviewed_by)->toBe($teacher->id);
});
