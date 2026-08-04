<?php

namespace App\Services;

use App\Models\QuizInstruction;
use App\Repositories\QuizInstruction\QuizInstructionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuizInstructionService
{
    public function __construct(
        private QuizInstructionRepositoryInterface $quizInstructionRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->quizInstructionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?QuizInstruction
    {
        return $this->quizInstructionRepository->find($id, $with);
    }

    public function create(array $data): QuizInstruction
    {
        return $this->quizInstructionRepository->create($data);
    }

    public function update(string $id, array $data): QuizInstruction
    {
        return $this->quizInstructionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->quizInstructionRepository->delete($id);
    }

    public function current(): ?QuizInstruction
    {
        return $this->quizInstructionRepository->current();
    }
}
