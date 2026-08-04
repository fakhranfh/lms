<?php

namespace App\Services;

use App\Models\AssessmentScore;
use App\Repositories\AssessmentScore\AssessmentScoreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentScoreService
{
    public function __construct(
        private AssessmentScoreRepositoryInterface $assessmentScoreRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentScoreRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentScore
    {
        return $this->assessmentScoreRepository->find($id, $with);
    }

    public function create(array $data): AssessmentScore
    {
        return $this->assessmentScoreRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentScore
    {
        return $this->assessmentScoreRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentScoreRepository->delete($id);
    }

    public function findByAttempt(string $attemptId): ?AssessmentScore
    {
        return $this->assessmentScoreRepository->findByAttempt($attemptId);
    }
}
