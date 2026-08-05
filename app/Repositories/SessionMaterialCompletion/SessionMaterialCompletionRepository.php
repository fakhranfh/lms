<?php

namespace App\Repositories\SessionMaterialCompletion;

use App\Models\SessionMaterialCompletion;
use Illuminate\Support\Collection;

class SessionMaterialCompletionRepository implements SessionMaterialCompletionRepositoryInterface
{
    public function completedMaterialIds(string $sessionId, string $userId): Collection
    {
        return SessionMaterialCompletion::query()
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->pluck('media_library_item_id');
    }

    public function markCompleted(string $sessionId, string $mediaLibraryItemId, string $userId): void
    {
        SessionMaterialCompletion::query()->firstOrCreate(
            [
                'session_id' => $sessionId,
                'media_library_item_id' => $mediaLibraryItemId,
                'user_id' => $userId,
            ],
            [
                'completed_at' => now(),
            ],
        );
    }

    public function markIncomplete(string $sessionId, string $mediaLibraryItemId, string $userId): void
    {
        SessionMaterialCompletion::query()
            ->where('session_id', $sessionId)
            ->where('media_library_item_id', $mediaLibraryItemId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function completedCountForSessions(Collection $sessionIds, string $userId): int
    {
        if ($sessionIds->isEmpty()) {
            return 0;
        }

        return SessionMaterialCompletion::query()
            ->whereIn('session_id', $sessionIds)
            ->where('user_id', $userId)
            ->count();
    }
}
