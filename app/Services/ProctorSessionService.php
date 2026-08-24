<?php

namespace App\Services;

use App\Models\ProctorSession;
use App\Repositories\ProctorSession\ProctorSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Redis;

class ProctorSessionService
{
    /**
     * TTL for the submitting flag, generous enough to outlast a finalization
     * job that ran late (queue backlog, retry) even though the happy path
     * clears it within the same request via dispatchSync.
     */
    private const SUBMITTING_TTL_SECONDS = 300;

    public function __construct(
        private ProctorSessionRepositoryInterface $proctorSessionRepository
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
     * Flags a session as submitting/finalizing in Redis instead of persisting
     * a transient "submitting" status row to the database.
     */
    public function markSubmitting(string $sessionId): void
    {
        Redis::setex($this->submittingKey($sessionId), self::SUBMITTING_TTL_SECONDS, 1);
    }

    public function clearSubmitting(string $sessionId): void
    {
        Redis::del($this->submittingKey($sessionId));
    }

    public function isSubmitting(string $sessionId): bool
    {
        return (bool) Redis::exists($this->submittingKey($sessionId));
    }

    private function submittingKey(string $sessionId): string
    {
        return "proctor_session_submitting:{$sessionId}";
    }
}
