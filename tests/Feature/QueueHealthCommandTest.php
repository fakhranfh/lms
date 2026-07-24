<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

test('queue:health reports healthy when redis is reachable', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);
    Queue::shouldReceive('connection')
        ->with('redis')
        ->andReturn(new class
        {
            public function size($queue)
            {
                return 3;
            }
        });

    $this->artisan('queue:health')->assertExitCode(0);
});

test('queue:health fails when redis is unreachable', function () {
    Redis::shouldReceive('connection->ping')->andThrow(new RuntimeException('connection refused'));

    $this->artisan('queue:health')->assertExitCode(1);
});
