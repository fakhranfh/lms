<?php

use App\Services\GradingQueueHealthService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

test('healthcheck reports redis connectivity', function () {
    $result = app(GradingQueueHealthService::class)->check();

    expect($result)->toHaveKeys(['redis_connected', 'queue_size', 'threshold_exceeded']);
    expect($result['redis_connected'])->toBeBool();
});

test('healthcheck reports redis as disconnected when the connection fails', function () {
    Redis::shouldReceive('connection->ping')->andThrow(new RuntimeException('connection refused'));

    $result = app(GradingQueueHealthService::class)->check();

    expect($result['redis_connected'])->toBeFalse();
    expect($result['queue_size'])->toBe(0);
    expect($result['threshold_exceeded'])->toBeFalse();
});

test('threshold_exceeded flips true once queue depth passes the alert threshold', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);
    Queue::shouldReceive('connection')
        ->with('redis')
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
