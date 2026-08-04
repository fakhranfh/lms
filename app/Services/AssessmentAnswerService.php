<?php

namespace App\Services;

use App\Models\AssessmentAnswer;
use App\Repositories\AssessmentAnswer\AssessmentAnswerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentAnswerService
{
    public function __construct(
        private AssessmentAnswerRepositoryInterface $assessmentAnswerRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentAnswerRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentAnswer
    {
        return $this->assessmentAnswerRepository->find($id, $with);
    }

    public function create(array $data): AssessmentAnswer
    {
        return $this->assessmentAnswerRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentAnswer
    {
        return $this->assessmentAnswerRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentAnswerRepository->delete($id);
    }

    public function findByAttempt(string $attemptId): ?AssessmentAnswer
    {
        return $this->assessmentAnswerRepository->findByAttempt($attemptId);
    }
}
