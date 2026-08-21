<?php

use App\Enums\ProctorSessionStatus;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ProctorSession;
use App\Models\User;
use App\Services\ProctorDisqualificationStatusService;

test('latest session for assessment returns the most recently started attempt session', function () {
    $assessment = Assessment::factory()->create();
    $user = User::factory()->create();

    $older = AssessmentAttempt::factory()->for($assessment)->create([
        'user_id' => $user->id,
        'attempt_number' => 1,
        'started_at' => now()->subHour(),
    ]);
    ProctorSession::factory()->for($older, 'attempt')->create(['status' => ProctorSessionStatus::Terminated]);

    $latest = AssessmentAttempt::factory()->for($assessment)->create([
        'user_id' => $user->id,
        'attempt_number' => 2,
        'started_at' => now(),
    ]);
    $latestSession = ProctorSession::factory()->for($latest, 'attempt')->create(['status' => ProctorSessionStatus::Submitting]);

    $found = app(ProctorDisqualificationStatusService::class)->latestSessionForAssessment($assessment->id, $user->id);

    expect($found->id)->toBe($latestSession->id);
});

test('latest session for assessment returns null when the user has no attempts', function () {
    $assessment = Assessment::factory()->create();
    $user = User::factory()->create();

    $found = app(ProctorDisqualificationStatusService::class)->latestSessionForAssessment($assessment->id, $user->id);

    expect($found)->toBeNull();
});
