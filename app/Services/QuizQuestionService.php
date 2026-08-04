<?php

namespace App\Services;

use App\Models\QuizQuestion;
use App\Repositories\QuizQuestion\QuizQuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuizQuestionService
{
    public function __construct(
        private QuizQuestionRepositoryInterface $quizQuestionRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->quizQuestionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?QuizQuestion
    {
        return $this->quizQuestionRepository->find($id, $with);
    }

    public function create(array $data): QuizQuestion
    {
        return $this->quizQuestionRepository->create($data);
    }

    public function update(string $id, array $data): QuizQuestion
    {
        return $this->quizQuestionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->quizQuestionRepository->delete($id);
    }
}
