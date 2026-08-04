<?php

namespace App\Services;

use App\Models\QuizQuestionOption;
use App\Repositories\QuizQuestionOption\QuizQuestionOptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuizQuestionOptionService
{
    public function __construct(
        private QuizQuestionOptionRepositoryInterface $quizQuestionOptionRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->quizQuestionOptionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?QuizQuestionOption
    {
        return $this->quizQuestionOptionRepository->find($id, $with);
    }

    public function create(array $data): QuizQuestionOption
    {
        return $this->quizQuestionOptionRepository->create($data);
    }

    public function update(string $id, array $data): QuizQuestionOption
    {
        return $this->quizQuestionOptionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->quizQuestionOptionRepository->delete($id);
    }
}
