<?php

namespace App\Jobs;

use App\Contracts\AiGradingProvider;
use App\Enums\SubmissionStatus;
use App\Repositories\Submission\SubmissionRepositoryInterface;
use App\Support\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GradeSubmissionJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 45;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $submissionId,
    ) {}

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 15];
    }

    /**
     * Execute the job.
     */
    public function handle(AiGradingProvider $provider, SubmissionRepositoryInterface $submissions): void
    {
        $submission = $submissions->find($this->submissionId, ['assignment.lesson.module.course']);

        if ($submission === null) {
            Log::warning('GradeSubmissionJob: submission not found', ['submission_id' => $this->submissionId]);

            return;
        }

        if ($submission->status->isTerminal()) {
            return;
        }

        $assignment = $submission->assignment;
        $schoolId = $assignment->lesson->module->course->school_id;

        app(CurrentSchool::class)->setSchoolId($schoolId);

        $submissions->update($submission->id, ['status' => SubmissionStatus::Processing]);

        try {
            $prompt = $provider->buildGradingPrompt($assignment, $submission->student_answer);
            $result = $provider->gradeEssay($submission->student_answer, $assignment->rubricItems(), $prompt);

            if (! $result['success']) {
                throw new RuntimeException($result['error'] ?? 'AI grading provider returned an unsuccessful response.');
            }

            $submissions->update($submission->id, [
                'status' => SubmissionStatus::Graded,
                'ai_score' => $result['score'],
                'ai_feedback' => $result['feedback'],
                'graded_at' => now(),
                'error_message' => null,
            ]);

            Log::info('GradeSubmissionJob: submission graded', [
                'submission_id' => $submission->id,
                'assignment_id' => $assignment->id,
                'score' => $result['score'],
            ]);
        } catch (Throwable $e) {
            $submissions->update($submission->id, [
                'status' => SubmissionStatus::Pending,
                'retry_count' => $submission->retry_count + 1,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('GradeSubmissionJob: grading attempt failed', [
                'submission_id' => $submission->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $submissions = app(SubmissionRepositoryInterface::class);

        if ($submissions->find($this->submissionId) === null) {
            return;
        }

        $submissions->update($this->submissionId, [
            'status' => SubmissionStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'AI grading failed after maximum retries.',
        ]);

        Log::critical('GradeSubmissionJob: submission permanently failed grading', [
            'submission_id' => $this->submissionId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
