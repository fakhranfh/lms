<?php

namespace App\Services;

use App\Models\SessionProgress;
use App\Repositories\SessionProgress\SessionProgressRepositoryInterface;

class SessionProgressService
{
    public function __construct(
        private SessionProgressRepositoryInterface $sessionProgressRepository
    ) {}

    public function find(string $sessionId, string $userId): ?SessionProgress
    {
        return $this->sessionProgressRepository->find($sessionId, $userId);
    }

    public function upsert(string $sessionId, string $userId, int $percent): SessionProgress
    {
        return $this->sessionProgressRepository->upsert($sessionId, $userId, $percent);
    }
}
