<?php

namespace App\Repositories\Redis;

use App\Repositories\Cache\CacheRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisRepository implements CacheRepositoryInterface
{
    public function isAvailable(): bool
    {
        try {
            return Redis::connection()->ping() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        return (bool) Redis::exists($key);
    }

    public function type(string $key): int|string
    {
        return Redis::type($key);
    }

    public function ttl(string $key): int
    {
        return Redis::ttl($key);
    }

    public function get(string $key): ?string
    {
        return Redis::get($key);
    }

    public function put(string $key, string $value, ?int $seconds = null): void
    {
        if ($seconds !== null) {
            Redis::setex($key, $seconds, $value);

            return;
        }

        Redis::set($key, $value);
    }

    public function expire(string $key, int $seconds): void
    {
        Redis::expire($key, $seconds);
    }

    public function forget(string $key): int
    {
        return Redis::del($key);
    }

    public function keys(string $pattern): array
    {
        return Redis::keys($pattern);
    }

    public function listRange(string $key, int $start, int $end): array
    {
        return Redis::lrange($key, $start, $end);
    }

    public function setMembers(string $key): array
    {
        return Redis::smembers($key);
    }

    public function sortedSetRange(string $key, int $start, int $end, array $options = []): array
    {
        return Redis::zrange($key, $start, $end, $options);
    }

    public function hashAll(string $key): array
    {
        return Redis::hgetall($key);
    }

    public function hashGet(string $key, string $field): mixed
    {
        return Redis::hget($key, $field);
    }

    public function hashSet(string $key, string $field, mixed $value): void
    {
        Redis::hset($key, $field, $value);
    }

    public function flush(): void
    {
        Redis::flushdb();
    }

    public function stats(): array
    {
        $info = Redis::info();

        return [
            'used_memory_human' => $info['used_memory_human'] ?? '-',
            'connected_clients' => $info['connected_clients'] ?? '-',
            'uptime_in_days' => $info['uptime_in_days'] ?? '-',
            'version' => $info['redis_version'] ?? '-',
        ];
    }

    public function totalKeyCount(): int
    {
        $info = Redis::info();
        $keyspace = $info['db'.config('database.redis.default.database', 0)] ?? null;

        if (! is_string($keyspace)) {
            return 0;
        }

        return preg_match('/keys=(\d+)/', $keyspace, $m) ? (int) $m[1] : 0;
    }

    public function keyPrefix(): string
    {
        return config('database.redis.options.prefix', '');
    }
}
