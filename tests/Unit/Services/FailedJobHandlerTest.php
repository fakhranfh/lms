<?php

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Services\FailedJobHandler;
use Illuminate\Support\Facades\Log;

test('handle marks submission as permanently failed and logs a critical alert', function () {
    Log::shouldReceive('critical')->once()->withArgs(function (string $message, array $context) {
        return str_contains($message, 'permanently failed')
            && $context['error'] === 'Gemini API timeout';
    });

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Processing,
        'retry_count' => 3,
    ]);

    app(FailedJobHandler::class)->handle($submission, new RuntimeException('Gemini API timeout'));

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Failed);
    expect($submission->error_message)->toBe('Gemini API timeout');
});

test('handle falls back to a default message when no exception is given', function () {
    Log::shouldReceive('critical')->once();

    $submission = Submission::factory()->create(['status' => SubmissionStatus::Processing]);

    app(FailedJobHandler::class)->handle($submission, null);

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Failed);
    expect($submission->error_message)->toBe('AI grading failed after maximum retries.');
});
