<?php

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ProctorSession;
use App\Models\ProctorSnapshot;
use App\Services\R2StorageService;

test('resetting final exam attempts deletes their proctor snapshot files from r2', function () {
    $assessment = Assessment::factory()->create(['type' => AssessmentType::TheoryFinalExam]);
    $attempt = AssessmentAttempt::factory()->for($assessment)->create();
    $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
    $snapshotOne = ProctorSnapshot::factory()->for($session, 'proctorSession')->create(['file_url' => 'proctor/session-1/webcam-1.jpg']);
    $snapshotTwo = ProctorSnapshot::factory()->for($session, 'proctorSession')->create(['file_url' => 'proctor/session-1/screen-1.jpg']);

    $r2Mock = $this->mock(R2StorageService::class);
    $r2Mock->shouldReceive('delete')->once()->with($snapshotOne->file_url)->andReturn(true);
    $r2Mock->shouldReceive('delete')->once()->with($snapshotTwo->file_url)->andReturn(true);

    $this->artisan('final-exam:reset', ['--force' => true])
        ->assertExitCode(0);

    $this->assertDatabaseMissing('assessment_attempts', ['id' => $attempt->id]);
    $this->assertDatabaseMissing('proctor_sessions', ['id' => $session->id]);
    $this->assertDatabaseMissing('proctor_snapshots', ['id' => $snapshotOne->id]);
});

test('resetting final exam attempts skips r2 deletion when there are no proctor snapshots', function () {
    $assessment = Assessment::factory()->create(['type' => AssessmentType::TheoryFinalExam]);
    AssessmentAttempt::factory()->for($assessment)->create();

    $r2Mock = $this->mock(R2StorageService::class);
    $r2Mock->shouldNotReceive('delete');

    $this->artisan('final-exam:reset', ['--force' => true])
        ->assertExitCode(0);
});
