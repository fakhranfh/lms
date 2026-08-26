<?php

use App\Services\GradingQueueHealthService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

test('healthcheck reports cache connectivity', function () {
    $result = app(GradingQueueHealthService::class)->check();

    expect($result)->toHaveKeys(['cache_connected', 'queue_size', 'threshold_exceeded']);
    expect($result['cache_connected'])->toBeBool();
});

test('healthcheck reports cache as disconnected when the connection fails', function () {
    Redis::shouldReceive('connection->ping')->andThrow(new RuntimeException('connection refused'));

    $result = app(GradingQueueHealthService::class)->check();

    expect($result['cache_connected'])->toBeFalse();
    expect($result['queue_size'])->toBe(0);
    expect($result['threshold_exceeded'])->toBeFalse();
});

test('threshold_exceeded flips true once queue depth passes the alert threshold', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);
    Queue::shouldReceive('connection')
        ->with(config('queue.grading_connection'))
        ->andReturn(new class
        {
            public function size($queue)
            {
                return GradingQueueHealthService::DEPTH_ALERT_THRESHOLD + 1;
            }
        });

    $result = app(GradingQueueHealthService::class)->check();

    expect($result['threshold_exceeded'])->toBeTrue();
});
