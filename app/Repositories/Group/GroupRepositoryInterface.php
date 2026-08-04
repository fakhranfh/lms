<?php

namespace App\Repositories\Group;

use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;

interface GroupRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Group;

    public function create(array $data): Group;

    public function update(string $id, array $data): Group;

    public function delete(string $id): int;

    public function forCourse(string $courseId): Collection;
}
