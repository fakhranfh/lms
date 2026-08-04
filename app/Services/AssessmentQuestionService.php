<?php

namespace App\Services;

use App\Models\AssessmentQuestion;
use App\Repositories\AssessmentQuestion\AssessmentQuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionService
{
    public function __construct(
        private AssessmentQuestionRepositoryInterface $assessmentQuestionRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentQuestionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentQuestion
    {
        return $this->assessmentQuestionRepository->find($id, $with);
    }

    public function create(array $data): AssessmentQuestion
    {
        return $this->assessmentQuestionRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentQuestion
    {
        return $this->assessmentQuestionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentQuestionRepository->delete($id);
    }
}
