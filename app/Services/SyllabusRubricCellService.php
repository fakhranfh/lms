<?php

namespace App\Services;

use App\Models\SyllabusRubricCell;
use App\Repositories\SyllabusRubricCell\SyllabusRubricCellRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRubricCellService
{
    public function __construct(
        private SyllabusRubricCellRepositoryInterface $syllabusRubricCellRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusRubricCellRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusRubricCell
    {
        return $this->syllabusRubricCellRepository->find($id, $with);
    }

    public function create(array $data): SyllabusRubricCell
    {
        return $this->syllabusRubricCellRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusRubricCell
    {
        return $this->syllabusRubricCellRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusRubricCellRepository->delete($id);
    }
}
