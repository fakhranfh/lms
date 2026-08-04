<?php

namespace App\Repositories\SyllabusLearningOutcome;

use App\Models\SyllabusLearningOutcome;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusLearningOutcomeRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusLearningOutcome;

    public function create(array $data): SyllabusLearningOutcome;

    public function update(string $id, array $data): SyllabusLearningOutcome;

    public function delete(string $id): int;
}
