<?php

namespace App\Repositories\Attendance;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Attendance;

    public function create(array $data): Attendance;

    public function update(string $id, array $data): Attendance;

    public function delete(string $id): int;

    public function findBySessionAndUser(string $sessionId, string $userId): ?Attendance;

    public function forUser(string $userId): Collection;
}
