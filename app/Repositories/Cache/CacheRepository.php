<?php

namespace App\Repositories\Cache;

use App\Repositories\Redis\RedisRepository;
use Throwable;

/**
 * Tries Redis for every cache operation and transparently falls back to the
 * database-backed store when Redis is unreachable, so features that depend
 * on the cache repository keep working without Redis running.
 *
 * isAvailable() is not covered by this fallback: it reports Redis's actual
 * connectivity, since callers use it as a Redis health signal.
 */
class CacheRepository implements CacheRepositoryInterface
{
    public function __construct(
        private RedisRepository $redis,
        private DatabaseCacheRepository $database,
    ) {}

    public function isAvailable(): bool
    {
        return $this->redis->isAvailable();
    }

    public function has(string $key): bool
    {
        return $this->attempt(fn () => $this->redis->has($key), fn () => $this->database->has($key));
    }

    public function type(string $key): int|string
    {
        return $this->attempt(fn () => $this->redis->type($key), fn () => $this->database->type($key));
    }

    public function ttl(string $key): int
    {
        return $this->attempt(fn () => $this->redis->ttl($key), fn () => $this->database->ttl($key));
    }

    public function get(string $key): ?string
    {
        return $this->attempt(fn () => $this->redis->get($key), fn () => $this->database->get($key));
    }

    public function put(string $key, string $value, ?int $seconds = null): void
    {
        $this->attempt(fn () => $this->redis->put($key, $value, $seconds), fn () => $this->database->put($key, $value, $seconds));
    }

    public function expire(string $key, int $seconds): void
    {
        $this->attempt(fn () => $this->redis->expire($key, $seconds), fn () => $this->database->expire($key, $seconds));
    }

    public function forget(string $key): int
    {
        return $this->attempt(fn () => $this->redis->forget($key), fn () => $this->database->forget($key));
    }

    public function keys(string $pattern): array
    {
        return $this->attempt(fn () => $this->redis->keys($pattern), fn () => $this->database->keys($pattern));
    }

    public function listRange(string $key, int $start, int $end): array
    {
        return $this->attempt(fn () => $this->redis->listRange($key, $start, $end), fn () => $this->database->listRange($key, $start, $end));
    }

    public function setMembers(string $key): array
    {
        return $this->attempt(fn () => $this->redis->setMembers($key), fn () => $this->database->setMembers($key));
    }

    public function sortedSetRange(string $key, int $start, int $end, array $options = []): array
    {
        return $this->attempt(fn () => $this->redis->sortedSetRange($key, $start, $end, $options), fn () => $this->database->sortedSetRange($key, $start, $end, $options));
    }

    public function hashAll(string $key): array
    {
        return $this->attempt(fn () => $this->redis->hashAll($key), fn () => $this->database->hashAll($key));
    }

    public function hashGet(string $key, string $field): mixed
    {
        return $this->attempt(fn () => $this->redis->hashGet($key, $field), fn () => $this->database->hashGet($key, $field));
    }

    public function hashSet(string $key, string $field, mixed $value): void
    {
        $this->attempt(fn () => $this->redis->hashSet($key, $field, $value), fn () => $this->database->hashSet($key, $field, $value));
    }

    public function hashDelete(string $key, string $field): void
    {
        $this->attempt(fn () => $this->redis->hashDelete($key, $field), fn () => $this->database->hashDelete($key, $field));
    }

    public function flush(): void
    {
        $this->attempt(fn () => $this->redis->flush(), fn () => $this->database->flush());
    }

    public function stats(): array
    {
        return $this->attempt(fn () => $this->redis->stats(), fn () => $this->database->stats());
    }

    public function totalKeyCount(): int
    {
        return $this->attempt(fn () => $this->redis->totalKeyCount(), fn () => $this->database->totalKeyCount());
    }

    public function keyPrefix(): string
    {
        return $this->attempt(fn () => $this->redis->keyPrefix(), fn () => $this->database->keyPrefix());
    }

    private function attempt(callable $viaRedis, callable $viaDatabase): mixed
    {
        try {
            return $viaRedis();
        } catch (Throwable) {
            return $viaDatabase();
        }
    }
}
