<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

test('queue:health reports healthy when cache is reachable', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);
    Queue::shouldReceive('connection')
        ->with(config('queue.grading_connection'))
        ->andReturn(new class
        {
            public function size($queue)
            {
                return 3;
            }
        });

    $this->artisan('queue:health')->assertExitCode(0);
});

test('queue:health fails when cache is unreachable', function () {
    Redis::shouldReceive('connection->ping')->andThrow(new RuntimeException('connection refused'));

    $this->artisan('queue:health')->assertExitCode(1);
});
