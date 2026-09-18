<?php

namespace App\Services;

use App\Repositories\Cache\CacheRepositoryInterface;

/**
 * Holds a teacher's bulk-select checkbox state for the Students index in
 * Redis, keyed per user, so navigating between pages (or a full page
 * refresh) doesn't lose which students were checked (same "state in Redis"
 * pattern as AttendanceDraftService).
 */
class StudentSelectionService
{
    public function __construct(
        private CacheRepositoryInterface $cacheRepository
    ) {}

    /**
     * @return array<int, string>
     */
    public function all(string $userId): array
    {
        return array_keys($this->cacheRepository->hashAll($this->key($userId)));
    }

    public function set(string $userId, string $studentId, bool $selected): void
    {
        if ($selected) {
            $this->cacheRepository->hashSet($this->key($userId), $studentId, '1');
        } else {
            $this->cacheRepository->hashDelete($this->key($userId), $studentId);
        }
    }

    /**
     * @param  array<int, string>  $studentIds
     */
    public function setMany(string $userId, array $studentIds, bool $selected): void
    {
        foreach ($studentIds as $studentId) {
            $this->set($userId, $studentId, $selected);
        }
    }

    public function clear(string $userId): void
    {
        $this->cacheRepository->forget($this->key($userId));
    }

    private function key(string $userId): string
    {
        return "student_selection:{$userId}";
    }
}
