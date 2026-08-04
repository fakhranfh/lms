<?php

namespace App\Repositories\QuizQuestion;

use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Collection;

interface QuizQuestionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?QuizQuestion;

    public function create(array $data): QuizQuestion;

    public function update(string $id, array $data): QuizQuestion;

    public function delete(string $id): int;
}
