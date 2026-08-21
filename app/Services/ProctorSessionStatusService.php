<?php

namespace App\Services;

use App\Models\ProctorSession;

class ProctorSessionStatusService
{
    public function __construct(
        private AssessmentAttemptService $assessmentAttemptService,
        private ProctorSessionService $proctorSessionService,
    ) {}

    public function latestSessionForAssessment(string $assessmentId, string $userId): ?ProctorSession
    {
        $latestAttempt = $this->assessmentAttemptService
            ->forAssessmentAndUser($assessmentId, $userId)
            ->sortByDesc('started_at')
            ->first();

        if ($latestAttempt === null) {
            return null;
        }

        return $this->proctorSessionService->findByAttempt($latestAttempt->id);
    }
}
