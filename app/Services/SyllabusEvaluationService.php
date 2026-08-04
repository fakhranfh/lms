<?php

namespace App\Services;

use App\Models\SyllabusEvaluation;
use App\Repositories\SyllabusEvaluation\SyllabusEvaluationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusEvaluationService
{
    public function __construct(
        private SyllabusEvaluationRepositoryInterface $syllabusEvaluationRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusEvaluationRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusEvaluation
    {
        return $this->syllabusEvaluationRepository->find($id, $with);
    }

    public function create(array $data): SyllabusEvaluation
    {
        return $this->syllabusEvaluationRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusEvaluation
    {
        return $this->syllabusEvaluationRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusEvaluationRepository->delete($id);
    }
}
