<?php

namespace App\Services;

use App\Models\SyllabusLearningOutcome;
use App\Repositories\SyllabusLearningOutcome\SyllabusLearningOutcomeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusLearningOutcomeService
{
    public function __construct(
        private SyllabusLearningOutcomeRepositoryInterface $syllabusLearningOutcomeRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusLearningOutcomeRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusLearningOutcome
    {
        return $this->syllabusLearningOutcomeRepository->find($id, $with);
    }

    public function create(array $data): SyllabusLearningOutcome
    {
        return $this->syllabusLearningOutcomeRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusLearningOutcome
    {
        return $this->syllabusLearningOutcomeRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusLearningOutcomeRepository->delete($id);
    }
}
