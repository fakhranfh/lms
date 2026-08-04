<?php

namespace App\Repositories\SyllabusRubricCell;

use App\Models\SyllabusRubricCell;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusRubricCellRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusRubricCell;

    public function create(array $data): SyllabusRubricCell;

    public function update(string $id, array $data): SyllabusRubricCell;

    public function delete(string $id): int;
}
