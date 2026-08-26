<?php

use App\Repositories\Cache\CacheRepositoryInterface;
use App\Services\GradingQueueHealthService;
use Illuminate\Support\Facades\Queue;

test('check reports healthy status when cache is up and queue depth is under threshold', function () {
    $this->mock(CacheRepositoryInterface::class)
        ->shouldReceive('isAvailable')->once()->andReturn(true);

    $queueConnection = Mockery::mock();
    $queueConnection->shouldReceive('size')->once()->andReturn(5);
    Queue::shouldReceive('connection')->with(config('queue.grading_connection'))->once()->andReturn($queueConnection);

    $result = app(GradingQueueHealthService::class)->check();

    expect($result)->toBe([
        'cache_connected' => true,
        'queue_size' => 5,
        'threshold_exceeded' => false,
    ]);
});

test('check reports cache as disconnected when the connection fails', function () {
    $this->mock(CacheRepositoryInterface::class)
        ->shouldReceive('isAvailable')->once()->andReturn(false);

    $result = app(GradingQueueHealthService::class)->check();

    expect($result)->toBe([
        'cache_connected' => false,
        'queue_size' => 0,
        'threshold_exceeded' => false,
    ]);
});

test('check flags threshold as exceeded when queue depth is above the limit', function () {
    $this->mock(CacheRepositoryInterface::class)
        ->shouldReceive('isAvailable')->once()->andReturn(true);

    $queueConnection = Mockery::mock();
    $queueConnection->shouldReceive('size')->once()->andReturn(GradingQueueHealthService::DEPTH_ALERT_THRESHOLD + 1);
    Queue::shouldReceive('connection')->with(config('queue.grading_connection'))->once()->andReturn($queueConnection);

    $result = app(GradingQueueHealthService::class)->check();

    expect($result['threshold_exceeded'])->toBeTrue();
});
