<?php

namespace App\Repositories\ProctorSnapshot;

use App\Models\ProctorSnapshot;
use Illuminate\Database\Eloquent\Collection;

interface ProctorSnapshotRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ProctorSnapshot;

    public function create(array $data): ProctorSnapshot;

    public function update(string $id, array $data): ProctorSnapshot;

    public function delete(string $id): int;

    public function forSession(string $proctorSessionId): Collection;
}
