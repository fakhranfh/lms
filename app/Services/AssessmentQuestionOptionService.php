<?php

namespace App\Services;

use App\Models\AssessmentQuestionOption;
use App\Repositories\AssessmentQuestionOption\AssessmentQuestionOptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionOptionService
{
    public function __construct(
        private AssessmentQuestionOptionRepositoryInterface $assessmentQuestionOptionRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentQuestionOptionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentQuestionOption
    {
        return $this->assessmentQuestionOptionRepository->find($id, $with);
    }

    public function create(array $data): AssessmentQuestionOption
    {
        return $this->assessmentQuestionOptionRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentQuestionOption
    {
        return $this->assessmentQuestionOptionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentQuestionOptionRepository->delete($id);
    }
}
