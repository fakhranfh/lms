<?php

namespace App\Repositories\CourseAttendanceSetting;

use App\Models\CourseAttendanceSetting;
use Illuminate\Database\Eloquent\Collection;

interface CourseAttendanceSettingRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?CourseAttendanceSetting;

    public function create(array $data): CourseAttendanceSetting;

    public function update(string $id, array $data): CourseAttendanceSetting;

    public function delete(string $id): int;

    public function findByCourse(string $courseId): ?CourseAttendanceSetting;
}
