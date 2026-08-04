<?php

namespace App\Services;

use App\Models\SyllabusRubricProficiencyLevel;
use App\Repositories\SyllabusRubricProficiencyLevel\SyllabusRubricProficiencyLevelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRubricProficiencyLevelService
{
    public function __construct(
        private SyllabusRubricProficiencyLevelRepositoryInterface $syllabusRubricProficiencyLevelRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusRubricProficiencyLevelRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusRubricProficiencyLevel
    {
        return $this->syllabusRubricProficiencyLevelRepository->find($id, $with);
    }

    public function create(array $data): SyllabusRubricProficiencyLevel
    {
        return $this->syllabusRubricProficiencyLevelRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusRubricProficiencyLevel
    {
        return $this->syllabusRubricProficiencyLevelRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusRubricProficiencyLevelRepository->delete($id);
    }
}
