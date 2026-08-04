<?php

namespace App\Services;

use App\Models\ProctorEvent;
use App\Repositories\ProctorEvent\ProctorEventRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProctorEventService
{
    public function __construct(
        private ProctorEventRepositoryInterface $proctorEventRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->proctorEventRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ProctorEvent
    {
        return $this->proctorEventRepository->find($id, $with);
    }

    public function create(array $data): ProctorEvent
    {
        return $this->proctorEventRepository->create($data);
    }

    public function update(string $id, array $data): ProctorEvent
    {
        return $this->proctorEventRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->proctorEventRepository->delete($id);
    }

    public function forSession(string $proctorSessionId): Collection
    {
        return $this->proctorEventRepository->forSession($proctorSessionId);
    }
}
