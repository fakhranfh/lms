<?php

use App\Contracts\AiGradingProvider;
use App\Enums\SubmissionStatus;
use App\Jobs\GradeSubmissionJob;
use App\Models\Assignment;
use App\Models\Submission;
use App\Repositories\Submission\SubmissionRepositoryInterface;
use App\Support\CurrentSchool;
use Illuminate\Support\Str;

function fakeProvider(array $result): void
{
    $mock = Mockery::mock(AiGradingProvider::class);
    $mock->shouldReceive('buildGradingPrompt')->andReturn('prompt');
    $mock->shouldReceive('gradeEssay')->andReturn($result);
    app()->instance(AiGradingProvider::class, $mock);
}

function submissions(): SubmissionRepositoryInterface
{
    return app(SubmissionRepositoryInterface::class);
}

test('successful grading transitions submission to graded with score and feedback', function () {
    $assignment = Assignment::factory()->create([
        'rubric' => [['item' => 'Clarity', 'points' => 20]],
    ]);
    $submission = Submission::factory()->for($assignment)->create([
        'status' => SubmissionStatus::Pending,
    ]);

    fakeProvider([
        'success' => true,
        'score' => 85.5,
        'feedback' => [['rubric_item' => 'Clarity', 'points_earned' => 18, 'points_max' => 20, 'comment' => 'Good.']],
    ]);

    (new GradeSubmissionJob($submission->id))->handle(app(AiGradingProvider::class), submissions());

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Graded);
    expect((float) $submission->ai_score)->toBe(85.5);
    expect($submission->ai_feedback)->toBeArray()->toHaveCount(1);
    expect($submission->graded_at)->not->toBeNull();
});

test('job sets tenant school id from submission assignment chain', function () {
    $assignment = Assignment::factory()->create();
    $submission = Submission::factory()->for($assignment)->create();

    fakeProvider(['success' => true, 'score' => 90.0, 'feedback' => []]);

    (new GradeSubmissionJob($submission->id))->handle(app(AiGradingProvider::class), submissions());

    $expectedSchoolId = $assignment->lesson->module->course->school_id;

    expect(app(CurrentSchool::class)->getSchoolId())->toBe($expectedSchoolId);
});

test('failed provider response throws and records error message with incremented retry_count', function () {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::Pending, 'retry_count' => 0]);

    fakeProvider(['success' => false, 'error' => 'Gemini API timeout']);

    expect(fn () => (new GradeSubmissionJob($submission->id))->handle(app(AiGradingProvider::class), submissions()))
        ->toThrow(RuntimeException::class, 'Gemini API timeout');

    $submission->refresh();

    expect($submission->retry_count)->toBe(1);
    expect($submission->error_message)->toBe('Gemini API timeout');
    expect($submission->status)->toBe(SubmissionStatus::Pending);
});

test('failed method marks submission as permanently failed after max retries', function () {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::Processing, 'retry_count' => 3]);

    $job = new GradeSubmissionJob($submission->id);
    $job->failed(new RuntimeException('Gemini API timeout'));

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Failed);
    expect($submission->error_message)->toBe('Gemini API timeout');
});

test('job is idempotent and skips already graded submissions', function () {
    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Graded,
        'ai_score' => 95.0,
        'graded_at' => now(),
    ]);

    $mock = Mockery::mock(AiGradingProvider::class);
    $mock->shouldNotReceive('gradeEssay');
    app()->instance(AiGradingProvider::class, $mock);

    (new GradeSubmissionJob($submission->id))->handle(app(AiGradingProvider::class), submissions());

    $submission->refresh();

    expect((float) $submission->ai_score)->toBe(95.0);
});

test('job logs and returns gracefully when submission is missing', function () {
    $job = new GradeSubmissionJob((string) Str::uuid());

    $mock = Mockery::mock(AiGradingProvider::class);
    $mock->shouldNotReceive('gradeEssay');
    app()->instance(AiGradingProvider::class, $mock);

    $job->handle(app(AiGradingProvider::class), submissions());

    expect(true)->toBeTrue();
});
