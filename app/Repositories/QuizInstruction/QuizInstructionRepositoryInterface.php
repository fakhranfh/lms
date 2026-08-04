<?php

namespace App\Repositories\QuizInstruction;

use App\Models\QuizInstruction;
use Illuminate\Database\Eloquent\Collection;

interface QuizInstructionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?QuizInstruction;

    public function create(array $data): QuizInstruction;

    public function update(string $id, array $data): QuizInstruction;

    public function delete(string $id): int;

    public function current(): ?QuizInstruction;
}
