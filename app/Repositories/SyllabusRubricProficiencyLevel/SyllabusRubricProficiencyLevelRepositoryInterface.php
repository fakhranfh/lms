<?php

namespace App\Repositories\SyllabusRubricProficiencyLevel;

use App\Models\SyllabusRubricProficiencyLevel;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusRubricProficiencyLevelRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusRubricProficiencyLevel;

    public function create(array $data): SyllabusRubricProficiencyLevel;

    public function update(string $id, array $data): SyllabusRubricProficiencyLevel;

    public function delete(string $id): int;
}
