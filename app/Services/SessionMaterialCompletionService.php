<?php

namespace App\Services;

use App\Repositories\SessionMaterialCompletion\SessionMaterialCompletionRepositoryInterface;
use Illuminate\Support\Collection;

class SessionMaterialCompletionService
{
    public function __construct(
        private SessionMaterialCompletionRepositoryInterface $repository
    ) {}

    /**
     * @return Collection<int, string>
     */
    public function completedMaterialIds(string $sessionId, string $userId): Collection
    {
        return $this->repository->completedMaterialIds($sessionId, $userId);
    }

    public function toggle(string $sessionId, string $mediaLibraryItemId, string $userId, bool $completed): void
    {
        if ($completed) {
            $this->repository->markCompleted($sessionId, $mediaLibraryItemId, $userId);

            return;
        }

        $this->repository->markIncomplete($sessionId, $mediaLibraryItemId, $userId);
    }
}
