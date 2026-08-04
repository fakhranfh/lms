<?php

namespace App\Services;

use App\Models\ProctorSnapshot;
use App\Repositories\ProctorSnapshot\ProctorSnapshotRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProctorSnapshotService
{
    public function __construct(
        private ProctorSnapshotRepositoryInterface $proctorSnapshotRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->proctorSnapshotRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ProctorSnapshot
    {
        return $this->proctorSnapshotRepository->find($id, $with);
    }

    public function create(array $data): ProctorSnapshot
    {
        return $this->proctorSnapshotRepository->create($data);
    }

    public function update(string $id, array $data): ProctorSnapshot
    {
        return $this->proctorSnapshotRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->proctorSnapshotRepository->delete($id);
    }

    public function forSession(string $proctorSessionId): Collection
    {
        return $this->proctorSnapshotRepository->forSession($proctorSessionId);
    }
}
