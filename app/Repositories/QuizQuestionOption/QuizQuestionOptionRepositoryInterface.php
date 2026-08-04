<?php

namespace App\Repositories\QuizQuestionOption;

use App\Models\QuizQuestionOption;
use Illuminate\Database\Eloquent\Collection;

interface QuizQuestionOptionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?QuizQuestionOption;

    public function create(array $data): QuizQuestionOption;

    public function update(string $id, array $data): QuizQuestionOption;

    public function delete(string $id): int;
}
