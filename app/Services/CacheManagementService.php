<?php

namespace App\Services;

use App\Repositories\Cache\CacheRepositoryInterface;

class CacheManagementService
{
    public function __construct(
        private CacheRepositoryInterface $cacheRepository
    ) {}

    public function exists(string $key): bool
    {
        return $this->cacheRepository->has($key);
    }

    public function typeOf(string $key): string
    {
        return $this->typeLabel($this->cacheRepository->type($key));
    }

    public function ttlOf(string $key): ?int
    {
        $ttl = $this->cacheRepository->ttl($key);

        return $ttl >= 0 ? $ttl : null;
    }

    public function readValue(string $key, string $type): string
    {
        return match ($type) {
            'string' => (string) $this->cacheRepository->get($key),
            'list' => json_encode($this->cacheRepository->listRange($key, 0, -1), JSON_PRETTY_PRINT),
            'set' => json_encode($this->cacheRepository->setMembers($key), JSON_PRETTY_PRINT),
            'zset' => json_encode($this->cacheRepository->sortedSetRange($key, 0, -1, ['withscores' => true]), JSON_PRETTY_PRINT),
            'hash' => json_encode($this->cacheRepository->hashAll($key), JSON_PRETTY_PRINT),
            default => '',
        };
    }

    public function updateStringValue(string $key, string $value): void
    {
        $ttl = $this->cacheRepository->ttl($key);
        $this->cacheRepository->put($key, $value);

        if ($ttl > 0) {
            $this->cacheRepository->expire($key, $ttl);
        }
    }

    public function deleteKey(string $key): void
    {
        $this->cacheRepository->forget($key);
    }

    public function flushDatabase(): void
    {
        $this->cacheRepository->flush();
    }

    /**
     * @return array<int, string>
     */
    public function matchingKeys(string $search): array
    {
        $pattern = $search !== '' ? "*{$search}*" : '*';

        return collect($this->cacheRepository->keys($pattern))
            ->map(fn (string $key) => $this->stripPrefix($key))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serverInfo(): array
    {
        return $this->cacheRepository->stats() + [
            'total_keys' => $this->cacheRepository->totalKeyCount(),
        ];
    }

    private function stripPrefix(string $key): string
    {
        $prefix = $this->cacheRepository->keyPrefix();

        return $prefix !== '' && str_starts_with($key, $prefix)
            ? substr($key, strlen($prefix))
            : $key;
    }

    private function typeLabel(int|string $type): string
    {
        if (is_string($type)) {
            return $type;
        }

        return match ($type) {
            1 => 'string',
            2 => 'set',
            3 => 'list',
            4 => 'zset',
            5 => 'hash',
            6 => 'stream',
            default => 'none',
        };
    }
}
