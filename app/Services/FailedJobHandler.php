<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Repositories\Submission\SubmissionRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class FailedJobHandler
{
    public function __construct(protected SubmissionRepositoryInterface $submissions) {}

    /**
     * Mark a submission as permanently failed after all grading retries are exhausted,
     * and alert the school admin (log-based until a notification channel exists).
     */
    public function handle(Submission $submission, ?Throwable $exception): void
    {
        $this->submissions->update($submission->id, [
            'status' => SubmissionStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'AI grading failed after maximum retries.',
        ]);

        $this->alertSchoolAdmin($submission, $exception);
    }

    /**
     * Send an alert to the school admin about the permanently failed submission.
     *
     * No notification channel exists yet for this; logging critically is the
     * current mechanism until that integration is built.
     */
    protected function alertSchoolAdmin(Submission $submission, ?Throwable $exception): void
    {
        Log::critical('GradeSubmissionJob: submission permanently failed grading', [
            'submission_id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'retry_count' => $submission->retry_count,
            'error' => $exception?->getMessage(),
        ]);
    }
}
