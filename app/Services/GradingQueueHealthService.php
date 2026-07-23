<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class GradingQueueHealthService
{
    /**
     * Queue depth above which a warning is logged.
     */
    public const DEPTH_ALERT_THRESHOLD = 1000;

    /**
     * Check Redis connectivity and the grading queue depth.
     *
     * @return array{redis_connected: bool, queue_size: int, threshold_exceeded: bool}
     */
    public function check(): array
    {
        $redisConnected = $this->isRedisConnected();
        $queueSize = $redisConnected ? $this->queueSize() : 0;
        $thresholdExceeded = $queueSize > self::DEPTH_ALERT_THRESHOLD;

        if (! $redisConnected) {
            Log::critical('GradingQueueHealthService: Redis connection unavailable');
        }

        if ($thresholdExceeded) {
            Log::warning('GradingQueueHealthService: grading queue depth exceeds threshold', [
                'queue_size' => $queueSize,
                'threshold' => self::DEPTH_ALERT_THRESHOLD,
            ]);
        }

        return [
            'redis_connected' => $redisConnected,
            'queue_size' => $queueSize,
            'threshold_exceeded' => $thresholdExceeded,
        ];
    }

    protected function isRedisConnected(): bool
    {
        try {
            return Redis::connection()->ping() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    protected function queueSize(): int
    {
        return Queue::connection('redis')->size(config('queue.connections.redis.queue', 'default'));
    }
}
