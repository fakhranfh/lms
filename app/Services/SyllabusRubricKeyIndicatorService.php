<?php

namespace App\Services;

use App\Models\SyllabusRubricKeyIndicator;
use App\Repositories\SyllabusRubricKeyIndicator\SyllabusRubricKeyIndicatorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRubricKeyIndicatorService
{
    public function __construct(
        private SyllabusRubricKeyIndicatorRepositoryInterface $syllabusRubricKeyIndicatorRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusRubricKeyIndicatorRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusRubricKeyIndicator
    {
        return $this->syllabusRubricKeyIndicatorRepository->find($id, $with);
    }

    public function create(array $data): SyllabusRubricKeyIndicator
    {
        return $this->syllabusRubricKeyIndicatorRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusRubricKeyIndicator
    {
        return $this->syllabusRubricKeyIndicatorRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusRubricKeyIndicatorRepository->delete($id);
    }
}
