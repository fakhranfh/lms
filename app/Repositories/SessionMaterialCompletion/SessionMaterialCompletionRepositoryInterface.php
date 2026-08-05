<?php

namespace App\Repositories\SessionMaterialCompletion;

use Illuminate\Support\Collection;

interface SessionMaterialCompletionRepositoryInterface
{
    /**
     * @return Collection<int, string>
     */
    public function completedMaterialIds(string $sessionId, string $userId): Collection;

    public function markCompleted(string $sessionId, string $mediaLibraryItemId, string $userId): void;

    public function markIncomplete(string $sessionId, string $mediaLibraryItemId, string $userId): void;

    /**
     * Count of the given sessions' materials the user has completed.
     *
     * @param  Collection<int, string>  $sessionIds
     */
    public function completedCountForSessions(Collection $sessionIds, string $userId): int;
}
