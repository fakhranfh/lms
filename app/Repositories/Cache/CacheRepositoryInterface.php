<?php

namespace App\Repositories\Cache;

interface CacheRepositoryInterface
{
    public function isAvailable(): bool;

    public function has(string $key): bool;

    public function type(string $key): int|string;

    public function ttl(string $key): int;

    public function get(string $key): ?string;

    public function put(string $key, string $value, ?int $seconds = null): void;

    public function expire(string $key, int $seconds): void;

    public function forget(string $key): int;

    /**
     * @return array<int, string>
     */
    public function keys(string $pattern): array;

    /**
     * @return array<int, string>
     */
    public function listRange(string $key, int $start, int $end): array;

    /**
     * @return array<int, string>
     */
    public function setMembers(string $key): array;

    /**
     * @param  array<string, mixed>  $options
     * @return array<int|string, mixed>
     */
    public function sortedSetRange(string $key, int $start, int $end, array $options = []): array;

    /**
     * @return array<string, string>
     */
    public function hashAll(string $key): array;

    public function hashGet(string $key, string $field): mixed;

    public function hashSet(string $key, string $field, mixed $value): void;

    public function hashDelete(string $key, string $field): void;

    public function flush(): void;

    /**
     * @return array<string, mixed>
     */
    public function stats(): array;

    /**
     * Number of keys currently stored.
     */
    public function totalKeyCount(): int;

    /**
     * Prefix applied to every key by the underlying store, if any.
     */
    public function keyPrefix(): string;
}
