<?php

namespace App\Repositories\AttendanceRequirement;

use App\Models\AttendanceRequirement;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceRequirementRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AttendanceRequirement;

    public function create(array $data): AttendanceRequirement;

    public function update(string $id, array $data): AttendanceRequirement;

    public function delete(string $id): int;

    public function forCourse(string $courseId): Collection;
}
