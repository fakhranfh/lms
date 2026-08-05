<?php

namespace App\Services;

use App\Repositories\SessionMaterialCompletion\SessionMaterialCompletionRepositoryInterface;
use Illuminate\Support\Collection;

class SessionMaterialCompletionService
{
    public function __construct(
        private SessionMaterialCompletionRepositoryInterface $repository,
        private SessionService $sessionService,
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

    /**
     * Percentage of a course's session materials the user has completed,
     * across all of the course's sessions.
     */
    public function courseProgressPercent(string $courseId, string $userId): int
    {
        $sessions = $this->sessionService->forCourse($courseId, ['materials']);

        $totalMaterials = $sessions->sum(fn ($session) => $session->materials->count());

        if ($totalMaterials === 0) {
            return 0;
        }

        $completedMaterials = $this->repository->completedCountForSessions($sessions->pluck('id'), $userId);

        return (int) round(min($completedMaterials, $totalMaterials) / $totalMaterials * 100);
    }
}
