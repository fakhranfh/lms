<?php

namespace App\Repositories\Period;

use App\Models\Period;
use Illuminate\Database\Eloquent\Collection;

interface PeriodRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Period;

    public function create(array $data): Period;

    public function update(string $id, array $data): Period;

    public function delete(string $id): int;
}
