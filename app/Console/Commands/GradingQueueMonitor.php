<?php

namespace App\Console\Commands;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use Illuminate\Console\Command;

class GradingQueueMonitor extends Command
{
    protected $signature = 'grading:monitor';

    protected $description = 'Show grading queue stats: pending, processing, failed counts and average grade time';

    public function handle(): int
    {
        $pending = Submission::query()->where('status', SubmissionStatus::Pending)->count();
        $processing = Submission::query()->where('status', SubmissionStatus::Processing)->count();
        $graded = Submission::query()->where('status', SubmissionStatus::Graded)->count();
        $failed = Submission::query()->where('status', SubmissionStatus::Failed)->count();

        $avgSeconds = Submission::query()
            ->where('status', SubmissionStatus::Graded)
            ->whereNotNull('graded_at')
            ->whereNotNull('submitted_at')
            ->get(['submitted_at', 'graded_at'])
            ->avg(fn (Submission $submission) => $submission->graded_at->diffInSeconds($submission->submitted_at, true));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Pending', $pending],
                ['Processing', $processing],
                ['Graded', $graded],
                ['Failed', $failed],
                ['Avg grade time', $avgSeconds !== null ? round((float) $avgSeconds, 1).'s' : 'n/a'],
            ]
        );

        return self::SUCCESS;
    }
}
