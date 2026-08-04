<?php

namespace App\Repositories\SyllabusEvaluation;

use App\Models\SyllabusEvaluation;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusEvaluationRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusEvaluation;

    public function create(array $data): SyllabusEvaluation;

    public function update(string $id, array $data): SyllabusEvaluation;

    public function delete(string $id): int;
}
