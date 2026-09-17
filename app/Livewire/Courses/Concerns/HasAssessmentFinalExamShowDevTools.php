<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\AssessmentAttemptService;
use App\Services\R2StorageService;

trait HasAssessmentFinalExamShowDevTools
{
    /**
     * Dev-only convenience for re-testing the exam flow without a database
     * reset: wipes a student's attempt(s) for this final exam, including
     * their R2 proctor recordings/screenshots, so they show as not
     * submitted again. FK cascadeOnDelete on assessment_attempts takes
     * care of scores, answers, question answers/scores, and proctor
     * sessions/events/snapshots.
     */
    public function resetStudentExam(string $userId, AssessmentAttemptService $assessmentAttemptService, R2StorageService $r2StorageService): void
    {
        abort_unless(app()->isLocal(), 404);
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $userId);

        foreach ($attempts as $attempt) {
            $attempt->loadMissing('proctorSession.snapshots');

            $attempt->proctorSession?->snapshots->each(
                fn ($snapshot) => $r2StorageService->delete($snapshot->file_url)
            );

            $assessmentAttemptService->delete($attempt->id);
        }

        $this->successMessage = __('Exam attempt reset for this student.');
    }
}
