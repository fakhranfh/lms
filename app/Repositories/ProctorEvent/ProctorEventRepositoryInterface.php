<?php

namespace App\Repositories\ProctorEvent;

use App\Models\ProctorEvent;
use Illuminate\Database\Eloquent\Collection;

interface ProctorEventRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ProctorEvent;

    public function create(array $data): ProctorEvent;

    public function update(string $id, array $data): ProctorEvent;

    public function delete(string $id): int;

    public function forSession(string $proctorSessionId): Collection;
}
