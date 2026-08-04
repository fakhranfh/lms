<?php

namespace App\Repositories\SyllabusRubricKeyIndicator;

use App\Models\SyllabusRubricKeyIndicator;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusRubricKeyIndicatorRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusRubricKeyIndicator;

    public function create(array $data): SyllabusRubricKeyIndicator;

    public function update(string $id, array $data): SyllabusRubricKeyIndicator;

    public function delete(string $id): int;
}
