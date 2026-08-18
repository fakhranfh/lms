<?php

namespace App\Console\Commands;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('final-exam:reset {--course=} {--force}')]
#[Description('Delete all Final Exam attempts (and their scores/proctor sessions) so every student shows as not started')]
class ResetFinalExamAttempts extends Command
{
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

        // Deleting the attempt cascades (FK cascadeOnDelete) to
        // assessment_scores, assessment_answers, assessment_quiz_answers,
        // assessment_question_scores, and proctor_sessions/events/snapshots.
        $query->get()->each->delete();

        $this->info("Reset {$count} Final Exam attempt(s) to not started.");

        return 0;
    }
}
