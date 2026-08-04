<?php

use App\Enums\ProctorReviewDecision;
use App\Models\AssessmentAttempt;
use App\Models\ProctorEvent;
use App\Models\ProctorSession;
use App\Models\ProctorSnapshot;
use App\Models\User;
use App\Services\ProctorSessionService;

test('proctor session is found by attempt and has events and snapshots', function () {
    $attempt = AssessmentAttempt::factory()->create();
    $proctorSession = ProctorSession::factory()->for($attempt, 'attempt')->create();
    ProctorEvent::factory()->for($proctorSession)->create();
    ProctorSnapshot::factory()->for($proctorSession)->create();

    $found = app(ProctorSessionService::class)->findByAttempt($attempt->id);

    expect($found->id)->toBe($proctorSession->id);
    expect($proctorSession->events()->count())->toBe(1);
    expect($proctorSession->snapshots()->count())->toBe(1);
});

test('reviewing a proctor session records the decision', function () {
    $proctorSession = ProctorSession::factory()->create();
    $reviewer = User::factory()->create();

    app(ProctorSessionService::class)->update($proctorSession->id, [
        'review_decision' => ProctorReviewDecision::Disqualified,
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
    ]);

    expect($proctorSession->fresh()->review_decision)->toBe(ProctorReviewDecision::Disqualified);
});

test('deleting a proctor session cascades to its events and snapshots', function () {
    $proctorSession = ProctorSession::factory()->create();
    $event = ProctorEvent::factory()->for($proctorSession)->create();
    $snapshot = ProctorSnapshot::factory()->for($proctorSession)->create();

    $proctorSession->delete();

    expect(ProctorEvent::find($event->id))->toBeNull();
    expect(ProctorSnapshot::find($snapshot->id))->toBeNull();
});
