<?php

namespace App\Console\Commands;

use App\Services\GradingQueueHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueHealthCommand extends Command
{
    protected $signature = 'queue:health';

    protected $description = 'Check cache connectivity, pending jobs, and failed jobs count';

    public function handle(GradingQueueHealthService $gradingQueueHealthService): int
    {
        $health = $gradingQueueHealthService->check();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->line('Cache connected: '.($health['cache_connected'] ? 'yes' : 'no'));
        $this->line("Pending jobs: {$health['queue_size']}");
        $this->line("Failed jobs: {$failedJobs}");

        if (! $health['cache_connected']) {
            $this->error('Cache connection unavailable.');

            return self::FAILURE;
        }

        $this->info('Queue is healthy.');

        return self::SUCCESS;
    }
}
