<?php

namespace App\Repositories\SyllabusEvaluationActivity;

use App\Models\SyllabusEvaluationActivity;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusEvaluationActivityRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusEvaluationActivity;

    public function create(array $data): SyllabusEvaluationActivity;

    public function update(string $id, array $data): SyllabusEvaluationActivity;

    public function delete(string $id): int;

    public function syncLearningOutcomes(string $id, array $learningOutcomeIds): void;
}
