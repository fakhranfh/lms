<?php

namespace App\Services;

use App\Models\ProctorSession;
use App\Repositories\ProctorSession\ProctorSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProctorSessionService
{
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
}
