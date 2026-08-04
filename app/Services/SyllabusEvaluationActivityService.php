<?php

namespace App\Services;

use App\Models\SyllabusEvaluationActivity;
use App\Repositories\SyllabusEvaluationActivity\SyllabusEvaluationActivityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusEvaluationActivityService
{
    public function __construct(
        private SyllabusEvaluationActivityRepositoryInterface $syllabusEvaluationActivityRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusEvaluationActivityRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusEvaluationActivity
    {
        return $this->syllabusEvaluationActivityRepository->find($id, $with);
    }

    public function create(array $data): SyllabusEvaluationActivity
    {
        return $this->syllabusEvaluationActivityRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusEvaluationActivity
    {
        return $this->syllabusEvaluationActivityRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusEvaluationActivityRepository->delete($id);
    }

    public function syncLearningOutcomes(string $id, array $learningOutcomeIds): void
    {
        $this->syllabusEvaluationActivityRepository->syncLearningOutcomes($id, $learningOutcomeIds);
    }
}
