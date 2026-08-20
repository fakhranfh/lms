<?php

namespace App\Console\Commands;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\ProctorSnapshot;
use App\Services\R2StorageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('final-exam:reset {--course=} {--force}')]
#[Description('Delete all Final Exam attempts (and their scores/proctor sessions/R2 files) so every student shows as not started')]
class ResetFinalExamAttempts extends Command
{
    public function __construct(private readonly R2StorageService $r2StorageService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = AssessmentAttempt::whereHas(
            'assessment',
            fn ($q) => $q->where('type', AssessmentType::TheoryFinalExam)
        );

        if ($courseId = $this->option('course')) {
            $hasFinalExam = Assessment::where('course_id', $courseId)
                ->where('type', AssessmentType::TheoryFinalExam)
                ->exists();

            if (! $hasFinalExam) {
                $this->error("No final exam assessments found for course {$courseId}.");

                return 1;
            }

            $query->whereHas('assessment', fn ($q) => $q->where('course_id', $courseId));
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No Final Exam attempts found. Nothing to reset.');

            return 0;
        }

        if (! $this->option('force') && ! $this->confirm("This will permanently delete {$count} Final Exam attempt(s), including scores and proctor sessions. Continue?")) {
            $this->warn('Aborted.');

            return 1;
        }

        $attempts = $query->with('proctorSession.snapshots')->get();

        $snapshots = $attempts->flatMap(function (AssessmentAttempt $attempt) {
            return $attempt->proctorSession === null ? [] : $attempt->proctorSession->snapshots;
        });

        $this->deleteSnapshotFiles($snapshots);

        // Deleting the attempt cascades (FK cascadeOnDelete) to
        // assessment_scores, assessment_answers, assessment_quiz_answers,
        // assessment_question_scores, and proctor_sessions/events/snapshots.
        $attempts->each->delete();

        $this->info("Reset {$count} Final Exam attempt(s) to not started.");

        return 0;
    }

    /**
     * @param  Collection<int, ProctorSnapshot>  $snapshots
     */
    private function deleteSnapshotFiles(Collection $snapshots): void
    {
        if ($snapshots->isEmpty()) {
            return;
        }

        $this->info("Deleting {$snapshots->count()} proctor file(s) from R2...");

        $snapshots->each(fn (ProctorSnapshot $snapshot) => $this->r2StorageService->delete($snapshot->file_url));
    }
}
