<?php

namespace App\Services;

use App\Repositories\Cache\CacheRepositoryInterface;

class ProctorExamAnswersService
{
    public function __construct(
        private CacheRepositoryInterface $cacheRepository
    ) {}

    /**
     * @return array<string, string>
     */
    public function all(string $attemptId): array
    {
        return $this->cacheRepository->hashAll($this->key($attemptId)) ?: [];
    }

    public function save(string $attemptId, string $questionId, string $value): void
    {
        $this->cacheRepository->hashSet($this->key($attemptId), $questionId, $value);
    }

    public function clear(string $attemptId): void
    {
        $this->cacheRepository->forget($this->key($attemptId));
    }

    private function key(string $attemptId): string
    {
        return "proctor_exam_answers:{$attemptId}";
    }
}
