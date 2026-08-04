<?php

namespace App\Services;

use App\Models\Period;
use App\Repositories\Period\PeriodRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PeriodService
{
    public function __construct(
        private PeriodRepositoryInterface $periodRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->periodRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Period
    {
        return $this->periodRepository->find($id, $with);
    }

    public function create(array $data): Period
    {
        return $this->periodRepository->create($data);
    }

    public function update(string $id, array $data): Period
    {
        return $this->periodRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->periodRepository->delete($id);
    }
}
