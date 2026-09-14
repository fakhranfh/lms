<?php

namespace App\Services;

use App\Repositories\Cache\CacheRepositoryInterface;

/**
 * Holds a teacher's in-progress "mark attendance" edits for a session before
 * they hit Save All, so pagination or a full page refresh doesn't lose them
 * (same "draft in Redis" pattern as ProctorExamAnswersService).
 */
class AttendanceDraftService
{
    public function __construct(
        private CacheRepositoryInterface $cacheRepository
    ) {}

    /**
     * @return array<string, array{status?: string, notes?: string}>
     */
    public function all(string $sessionId): array
    {
        $drafts = [];

        foreach ($this->cacheRepository->hashAll($this->key($sessionId)) as $field => $value) {
            [$userId, $attribute] = explode('.', $field, 2);
            $drafts[$userId][$attribute] = $value;
        }

        return $drafts;
    }

    public function save(string $sessionId, string $userId, string $attribute, string $value): void
    {
        $this->cacheRepository->hashSet($this->key($sessionId), "{$userId}.{$attribute}", $value);
    }

    public function clear(string $sessionId): void
    {
        $this->cacheRepository->forget($this->key($sessionId));
    }

    /**
     * Removes one user's drafted edits once their attendance has actually
     * been saved, so a stale draft never lingers past the save that covers it.
     */
    public function forgetUser(string $sessionId, string $userId): void
    {
        $this->cacheRepository->hashDelete($this->key($sessionId), "{$userId}.status");
        $this->cacheRepository->hashDelete($this->key($sessionId), "{$userId}.notes");
    }

    private function key(string $sessionId): string
    {
        return "attendance_draft:{$sessionId}";
    }
}
