<?php

namespace App\Services;

use App\Models\AssessmentQuizAnswer;
use App\Repositories\AssessmentQuizAnswer\AssessmentQuizAnswerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuizAnswerService
{
    public function __construct(
        private AssessmentQuizAnswerRepositoryInterface $assessmentQuizAnswerRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentQuizAnswerRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentQuizAnswer
    {
        return $this->assessmentQuizAnswerRepository->find($id, $with);
    }

    public function create(array $data): AssessmentQuizAnswer
    {
        return $this->assessmentQuizAnswerRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentQuizAnswer
    {
        return $this->assessmentQuizAnswerRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentQuizAnswerRepository->delete($id);
    }

    /**
     * @return Collection<int, AssessmentQuizAnswer>
     */
    public function forAttempt(string $attemptId): Collection
    {
        return $this->assessmentQuizAnswerRepository->forAttempt($attemptId);
    }
}
