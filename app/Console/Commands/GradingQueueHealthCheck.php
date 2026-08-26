<?php

namespace App\Console\Commands;

use App\Services\GradingQueueHealthService;
use Illuminate\Console\Command;

class GradingQueueHealthCheck extends Command
{
    protected $signature = 'grading:health';

    protected $description = 'Check cache connectivity and grading queue depth';

    public function handle(GradingQueueHealthService $service): int
    {
        $result = $service->check();

        $this->line('Cache connected: '.($result['cache_connected'] ? 'yes' : 'no'));
        $this->line("Queue size: {$result['queue_size']}");

        if (! $result['cache_connected']) {
            $this->error('Cache connection unavailable.');

            return self::FAILURE;
        }

        if ($result['threshold_exceeded']) {
            $threshold = GradingQueueHealthService::DEPTH_ALERT_THRESHOLD;
            $this->warn("Queue depth exceeds alert threshold ({$threshold}).");

            return self::FAILURE;
        }

        $this->info('Grading queue is healthy.');

        return self::SUCCESS;
    }
}
