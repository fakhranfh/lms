<?php

namespace App\Repositories\SessionProgress;

use App\Models\SessionProgress;

interface SessionProgressRepositoryInterface
{
    public function find(string $sessionId, string $userId): ?SessionProgress;

    public function upsert(string $sessionId, string $userId, int $percent): SessionProgress;
}
