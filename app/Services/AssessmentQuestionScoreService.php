<?php

namespace App\Services;

use App\Models\AssessmentQuestionScore;
use App\Repositories\AssessmentQuestionScore\AssessmentQuestionScoreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionScoreService
{
    public function __construct(
        private AssessmentQuestionScoreRepositoryInterface $assessmentQuestionScoreRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentQuestionScoreRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentQuestionScore
    {
        return $this->assessmentQuestionScoreRepository->find($id, $with);
    }

    public function create(array $data): AssessmentQuestionScore
    {
        return $this->assessmentQuestionScoreRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentQuestionScore
    {
        return $this->assessmentQuestionScoreRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentQuestionScoreRepository->delete($id);
    }

    public function findByAttempt(string $attemptId, array $with = []): Collection
    {
        return $this->assessmentQuestionScoreRepository->findByAttempt($attemptId, $with);
    }
}
