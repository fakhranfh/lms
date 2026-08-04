<?php

namespace App\Repositories\SyllabusClassPolicy;

use App\Models\SyllabusClassPolicy;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusClassPolicyRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SyllabusClassPolicy;

    public function create(array $data): SyllabusClassPolicy;

    public function update(string $id, array $data): SyllabusClassPolicy;

    public function delete(string $id): int;
}
