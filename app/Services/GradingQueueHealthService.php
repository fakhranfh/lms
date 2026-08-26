<?php

namespace App\Services;

use App\Repositories\Cache\CacheRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class GradingQueueHealthService
{
    /**
     * Queue depth above which a warning is logged.
     */
    public const DEPTH_ALERT_THRESHOLD = 1000;

    public function __construct(
        private CacheRepositoryInterface $cacheRepository
    ) {}

    /**
     * Check cache connectivity and the grading queue depth.
     *
     * @return array{cache_connected: bool, queue_size: int, threshold_exceeded: bool}
     */
    public function check(): array
    {
        $cacheConnected = $this->isCacheConnected();
        $queueSize = $cacheConnected ? $this->queueSize() : 0;
        $thresholdExceeded = $queueSize > self::DEPTH_ALERT_THRESHOLD;

        if (! $cacheConnected) {
            Log::critical('GradingQueueHealthService: cache connection unavailable');
        }

        if ($thresholdExceeded) {
            Log::warning('GradingQueueHealthService: grading queue depth exceeds threshold', [
                'queue_size' => $queueSize,
                'threshold' => self::DEPTH_ALERT_THRESHOLD,
            ]);
        }

        return [
            'cache_connected' => $cacheConnected,
            'queue_size' => $queueSize,
            'threshold_exceeded' => $thresholdExceeded,
        ];
    }

    protected function isCacheConnected(): bool
    {
        return $this->cacheRepository->isAvailable();
    }

    protected function queueSize(): int
    {
        $connection = config('queue.grading_connection');

        return Queue::connection($connection)->size(config("queue.connections.{$connection}.queue", 'default'));
    }
}
