<?php

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;

test('grading:monitor displays submission status counts and average grade time', function () {
    $assignment = Assignment::factory()->create();

    Submission::factory()->for($assignment)->create(['status' => SubmissionStatus::Pending]);
    Submission::factory()->for($assignment)->create(['status' => SubmissionStatus::Processing]);
    Submission::factory()->for($assignment)->create(['status' => SubmissionStatus::Failed]);
    Submission::factory()->for($assignment)->create([
        'status' => SubmissionStatus::Graded,
        'submitted_at' => now()->subSeconds(30),
        'graded_at' => now(),
    ]);

    $this->artisan('grading:monitor')
        ->assertSuccessful()
        ->expectsTable(
            ['Metric', 'Value'],
            [
                ['Pending', 1],
                ['Processing', 1],
                ['Graded', 1],
                ['Failed', 1],
                ['Avg grade time', '30s'],
            ]
        );
});

test('grading:monitor reports n/a average when no graded submissions exist', function () {
    $this->artisan('grading:monitor')
        ->assertSuccessful()
        ->expectsTable(
            ['Metric', 'Value'],
            [
                ['Pending', 0],
                ['Processing', 0],
                ['Graded', 0],
                ['Failed', 0],
                ['Avg grade time', 'n/a'],
            ]
        );
});
