<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class QueueHealthCommand extends Command
{
    protected $signature = 'queue:health';

    protected $description = 'Check Redis connectivity, pending jobs, and failed jobs count';

    public function handle(): int
    {
        $redisConnected = $this->isRedisConnected();
        $pendingJobs = $redisConnected ? $this->pendingJobsCount() : 0;
        $failedJobs = DB::table('failed_jobs')->count();

        $this->line('Redis connected: '.($redisConnected ? 'yes' : 'no'));
        $this->line("Pending jobs: {$pendingJobs}");
        $this->line("Failed jobs: {$failedJobs}");

        if (! $redisConnected) {
            $this->error('Redis connection unavailable.');

            return self::FAILURE;
        }

        $this->info('Queue is healthy.');

        return self::SUCCESS;
    }

    private function isRedisConnected(): bool
    {
        try {
            return Redis::connection()->ping() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    private function pendingJobsCount(): int
    {
        return Queue::connection('redis')->size(config('queue.connections.redis.queue', 'default'));
    }
}
