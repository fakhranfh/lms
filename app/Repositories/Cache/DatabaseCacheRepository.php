<?php

namespace App\Repositories\Cache;

use App\Models\CacheRepositoryEntry;

/**
 * Database-backed fallback for CacheRepositoryInterface, used when Redis is
 * unreachable. Only supports the scalar and hash operations the application
 * actually relies on (see App\Repositories\Cache\CacheRepository); list,
 * set, and sorted-set operations have no SQL-backed equivalent here.
 */
class DatabaseCacheRepository implements CacheRepositoryInterface
{
    private const SCALAR_FIELD = '';

    public function isAvailable(): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return $this->query($key, self::SCALAR_FIELD)->exists();
    }

    public function type(string $key): int|string
    {
        return '';
    }

    public function ttl(string $key): int
    {
        $entry = $this->query($key, self::SCALAR_FIELD, includingExpired: true)->first();

        if (! $entry) {
            return -2;
        }

        if (! $entry->expires_at) {
            return -1;
        }

        return max(0, (int) now()->diffInSeconds($entry->expires_at, false));
    }

    public function get(string $key): ?string
    {
        return $this->query($key, self::SCALAR_FIELD)->value('value');
    }

    public function put(string $key, string $value, ?int $seconds = null): void
    {
        CacheRepositoryEntry::updateOrCreate(
            ['key' => $key, 'field' => self::SCALAR_FIELD],
            ['value' => $value, 'expires_at' => $seconds !== null ? now()->addSeconds($seconds) : null],
        );
    }

    public function expire(string $key, int $seconds): void
    {
        CacheRepositoryEntry::where('key', $key)
            ->where('field', self::SCALAR_FIELD)
            ->update(['expires_at' => now()->addSeconds($seconds)]);
    }

    public function forget(string $key): int
    {
        return CacheRepositoryEntry::where('key', $key)->delete();
    }

    public function keys(string $pattern): array
    {
        return CacheRepositoryEntry::where('key', 'like', str_replace('*', '%', $pattern))
            ->pluck('key')
            ->unique()
            ->values()
            ->all();
    }

    public function listRange(string $key, int $start, int $end): array
    {
        return [];
    }

    public function setMembers(string $key): array
    {
        return [];
    }

    public function sortedSetRange(string $key, int $start, int $end, array $options = []): array
    {
        return [];
    }

    public function hashAll(string $key): array
    {
        return $this->query($key)
            ->where('field', '!=', self::SCALAR_FIELD)
            ->pluck('value', 'field')
            ->all();
    }

    public function hashGet(string $key, string $field): mixed
    {
        return $this->query($key, $field)->value('value');
    }

    public function hashSet(string $key, string $field, mixed $value): void
    {
        CacheRepositoryEntry::updateOrCreate(
            ['key' => $key, 'field' => $field],
            ['value' => $value],
        );
    }

    public function hashDelete(string $key, string $field): void
    {
        CacheRepositoryEntry::where('key', $key)->where('field', $field)->delete();
    }

    public function flush(): void
    {
        CacheRepositoryEntry::query()->delete();
    }

    public function stats(): array
    {
        return [
            'used_memory_human' => '-',
            'connected_clients' => '-',
            'uptime_in_days' => '-',
            'version' => 'database-fallback',
        ];
    }

    public function totalKeyCount(): int
    {
        return CacheRepositoryEntry::query()->distinct('key')->count('key');
    }

    public function keyPrefix(): string
    {
        return '';
    }

    private function query(string $key, ?string $field = null, bool $includingExpired = false)
    {
        $query = CacheRepositoryEntry::where('key', $key);

        if ($field !== null) {
            $query->where('field', $field);
        }

        if (! $includingExpired) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
        }

        return $query;
    }
}
