<?php

namespace App\Services;

use App\Models\Quiz;
use App\Repositories\Quiz\QuizRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuizService
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->quizRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Quiz
    {
        return $this->quizRepository->find($id, $with);
    }

    public function create(array $data): Quiz
    {
        return $this->quizRepository->create($data);
    }

    public function update(string $id, array $data): Quiz
    {
        return $this->quizRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->quizRepository->delete($id);
    }

    public function findByAssessment(string $assessmentId, array $with = []): ?Quiz
    {
        return $this->quizRepository->findByAssessment($assessmentId, $with);
    }
}
