<?php

namespace App\Repositories\SessionProgress;

use App\Models\SessionProgress;

class SessionProgressRepository implements SessionProgressRepositoryInterface
{
    public function find(string $sessionId, string $userId): ?SessionProgress
    {
        return SessionProgress::query()
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    public function upsert(string $sessionId, string $userId, int $percent): SessionProgress
    {
        return SessionProgress::query()->updateOrCreate(
            ['session_id' => $sessionId, 'user_id' => $userId],
            ['percent' => $percent],
        );
    }
}
