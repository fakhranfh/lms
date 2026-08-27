<?php

namespace App\Services;

use App\Models\ProctorSession;
use App\Repositories\Cache\CacheRepositoryInterface;
use App\Repositories\ProctorSession\ProctorSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProctorSessionService
{
    /**
     * TTL for the submitting flag, generous enough to outlast a finalization
     * job that ran late (queue backlog, retry) even though the happy path
     * clears it within the same request via dispatchSync.
     */
    private const SUBMITTING_TTL_SECONDS = 300;

    /**
     * TTL for the preflight-passed flag, generous enough to cover the time
     * between the camera/screen checks succeeding and the student confirming
     * the "Start Exam?" dialog.
     */
    private const PREFLIGHT_TTL_SECONDS = 300;

    public function __construct(
        private ProctorSessionRepositoryInterface $proctorSessionRepository,
        private CacheRepositoryInterface $cacheRepository,
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->proctorSessionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ProctorSession
    {
        return $this->proctorSessionRepository->find($id, $with);
    }

    public function create(array $data): ProctorSession
    {
        return $this->proctorSessionRepository->create($data);
    }

    public function update(string $id, array $data): ProctorSession
    {
        return $this->proctorSessionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->proctorSessionRepository->delete($id);
    }

    public function findByAttempt(string $attemptId, array $with = []): ?ProctorSession
    {
        return $this->proctorSessionRepository->findByAttempt($attemptId, $with);
    }

    /**
     * Flags a session as submitting/finalizing in the cache instead of persisting
     * a transient "submitting" status row to the database.
     */
    public function markSubmitting(string $sessionId): void
    {
        $this->cacheRepository->put($this->submittingKey($sessionId), '1', self::SUBMITTING_TTL_SECONDS);
    }

    public function clearSubmitting(string $sessionId): void
    {
        $this->cacheRepository->forget($this->submittingKey($sessionId));
    }

    public function isSubmitting(string $sessionId): bool
    {
        return $this->cacheRepository->has($this->submittingKey($sessionId));
    }

    private function submittingKey(string $sessionId): string
    {
        return "proctor_session_submitting:{$sessionId}";
    }

    /**
     * Flags that a student has passed the camera/screen-share preflight
     * check for an assessment, so startAttempt() can verify it server-side
     * instead of trusting a client-supplied flag.
     */
    public function markPreflightPassed(string $assessmentId, string $userId): void
    {
        $this->cacheRepository->put($this->preflightKey($assessmentId, $userId), '1', self::PREFLIGHT_TTL_SECONDS);
    }

    public function clearPreflightPassed(string $assessmentId, string $userId): void
    {
        $this->cacheRepository->forget($this->preflightKey($assessmentId, $userId));
    }

    public function hasPassedPreflight(string $assessmentId, string $userId): bool
    {
        return $this->cacheRepository->has($this->preflightKey($assessmentId, $userId));
    }

    private function preflightKey(string $assessmentId, string $userId): string
    {
        return "proctor_preflight_passed:{$assessmentId}:{$userId}";
    }
}
