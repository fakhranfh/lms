<?php

use App\Services\GradingQueueHealthService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

test('check reports healthy status when redis is up and queue depth is under threshold', function () {
    $redisConnection = Mockery::mock();
    $redisConnection->shouldReceive('ping')->once()->andReturn(true);
    Redis::shouldReceive('connection')->once()->andReturn($redisConnection);

    $queueConnection = Mockery::mock();
    $queueConnection->shouldReceive('size')->once()->andReturn(5);
    Queue::shouldReceive('connection')->with('redis')->once()->andReturn($queueConnection);

    $result = app(GradingQueueHealthService::class)->check();

    expect($result)->toBe([
        'redis_connected' => true,
        'queue_size' => 5,
        'threshold_exceeded' => false,
    ]);
});

test('check reports redis as disconnected when the connection throws', function () {
    Redis::shouldReceive('connection')->once()->andThrow(new RuntimeException('connection refused'));

    $result = app(GradingQueueHealthService::class)->check();

    expect($result)->toBe([
        'redis_connected' => false,
        'queue_size' => 0,
        'threshold_exceeded' => false,
    ]);
});

test('check flags threshold as exceeded when queue depth is above the limit', function () {
    $redisConnection = Mockery::mock();
    $redisConnection->shouldReceive('ping')->once()->andReturn(true);
    Redis::shouldReceive('connection')->once()->andReturn($redisConnection);

    $queueConnection = Mockery::mock();
    $queueConnection->shouldReceive('size')->once()->andReturn(GradingQueueHealthService::DEPTH_ALERT_THRESHOLD + 1);
    Queue::shouldReceive('connection')->with('redis')->once()->andReturn($queueConnection);

    $result = app(GradingQueueHealthService::class)->check();

    expect($result['threshold_exceeded'])->toBeTrue();
});
