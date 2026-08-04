<?php

namespace App\Repositories\Assessment;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Assessment;

    public function create(array $data): Assessment;

    public function update(string $id, array $data): Assessment;

    public function delete(string $id): int;

    public function forCourse(string $courseId): Collection;
}
