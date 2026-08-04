<?php

namespace App\Repositories\ProctorSession;

use App\Models\ProctorSession;
use Illuminate\Database\Eloquent\Collection;

interface ProctorSessionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ProctorSession;

    public function create(array $data): ProctorSession;

    public function update(string $id, array $data): ProctorSession;

    public function delete(string $id): int;

    public function findByAttempt(string $attemptId, array $with = []): ?ProctorSession;
}
