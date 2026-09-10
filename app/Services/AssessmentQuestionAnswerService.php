<?php

namespace App\Services;

use App\Models\AssessmentQuestionAnswer;
use App\Repositories\AssessmentQuestionAnswer\AssessmentQuestionAnswerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionAnswerService
{
    public function __construct(
        private AssessmentQuestionAnswerRepositoryInterface $assessmentQuestionAnswerRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentQuestionAnswerRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentQuestionAnswer
    {
        return $this->assessmentQuestionAnswerRepository->find($id, $with);
    }

    public function create(array $data): AssessmentQuestionAnswer
    {
        return $this->assessmentQuestionAnswerRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentQuestionAnswer
    {
        return $this->assessmentQuestionAnswerRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentQuestionAnswerRepository->delete($id);
    }

    /**
     * @return Collection<int, AssessmentQuestionAnswer>
     */
    public function forAttempt(string $attemptId): Collection
    {
        return $this->assessmentQuestionAnswerRepository->forAttempt($attemptId);
    }
}
