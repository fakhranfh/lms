<?php

namespace App\Jobs;

use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSessionStatus;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentScoreService;
use App\Services\AssessmentService;
use App\Services\GradebookScoringService;
use App\Services\ProctorSessionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class FinalizeProctorDisqualificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $attemptId,
        public string $reason,
    ) {}

    public function handle(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentScoreService $assessmentScoreService,
        ProctorSessionService $proctorSessionService,
        AssessmentService $assessmentService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        $feedback = __('Disqualified: cheating detected during the exam (:reason).', ['reason' => $this->reason]);

        $attempt = $assessmentAttemptService->update($this->attemptId, ['submitted_at' => now()]);

        $score = $assessmentScoreService->findByAttempt($this->attemptId);
        if ($score) {
            $assessmentScoreService->update($score->id, ['score' => 0, 'feedback' => $feedback]);
        } else {
            $assessmentScoreService->create([
                'assessment_attempt_id' => $this->attemptId,
                'score' => 0,
                'graded_at' => now(),
                'feedback' => $feedback,
            ]);
        }

        $assessment = $assessmentService->find($attempt->assessment_id, ['course']);
        if ($assessment !== null && $attempt->user_id !== null) {
            $gradebookScoringService->recomputeForUser($assessment->course, $attempt->user_id);
        }

        $session = $proctorSessionService->findByAttempt($this->attemptId);
        if ($session !== null) {
            $proctorSessionService->update($session->id, [
                'status' => ProctorSessionStatus::Terminated,
                'ended_at' => now(),
                'review_decision' => ProctorReviewDecision::Disqualified,
                'reviewed_at' => now(),
                'review_notes' => $feedback,
            ]);
            $proctorSessionService->clearSubmitting($session->id);
        }

        Redis::del("proctor_exam_answers:{$this->attemptId}");
    }
}
