<?php

use App\Repositories\Cache\CacheRepository;
use App\Repositories\Cache\DatabaseCacheRepository;
use App\Repositories\Redis\RedisRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

function unavailableRedisRepository(): RedisRepository
{
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('ping')->andThrow(new RuntimeException('connection refused'));
    Redis::shouldReceive('connection')->andReturn($connection);

    foreach (['exists', 'get', 'setex', 'set', 'del', 'hgetall', 'hget', 'hset', 'hdel'] as $method) {
        Redis::shouldReceive($method)->andThrow(new RuntimeException('connection refused'));
    }

    return new RedisRepository;
}

test('isAvailable reports redis as down without touching the database fallback', function () {
    $repository = new CacheRepository(unavailableRedisRepository(), new DatabaseCacheRepository);

    expect($repository->isAvailable())->toBeFalse();
});

test('scalar reads and writes fall back to the database when redis is unreachable', function () {
    $repository = new CacheRepository(unavailableRedisRepository(), new DatabaseCacheRepository);

    expect($repository->has('proctor_session_submitting:1'))->toBeFalse();

    $repository->put('proctor_session_submitting:1', '1', 300);

    expect($repository->has('proctor_session_submitting:1'))->toBeTrue();

    $repository->forget('proctor_session_submitting:1');

    expect($repository->has('proctor_session_submitting:1'))->toBeFalse();
});

test('hash reads and writes fall back to the database when redis is unreachable', function () {
    $repository = new CacheRepository(unavailableRedisRepository(), new DatabaseCacheRepository);

    $repository->hashSet('attendance_draft:1', 'user-1.status', 'present');

    expect($repository->hashAll('attendance_draft:1'))->toBe(['user-1.status' => 'present']);

    $repository->hashDelete('attendance_draft:1', 'user-1.status');

    expect($repository->hashAll('attendance_draft:1'))->toBe([]);
});
