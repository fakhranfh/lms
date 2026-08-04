<?php

namespace App\Repositories\CourseAttendanceSetting;

use App\Models\CourseAttendanceSetting;
use Illuminate\Database\Eloquent\Collection;

class CourseAttendanceSettingRepository implements CourseAttendanceSettingRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = CourseAttendanceSetting::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?CourseAttendanceSetting
    {
        return CourseAttendanceSetting::with($with)->find($id);
    }

    public function create(array $data): CourseAttendanceSetting
    {
        return CourseAttendanceSetting::create($data);
    }

    public function update(string $id, array $data): CourseAttendanceSetting
    {
        $model = CourseAttendanceSetting::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return CourseAttendanceSetting::destroy($id);
    }

    public function findByCourse(string $courseId): ?CourseAttendanceSetting
    {
        return CourseAttendanceSetting::where('course_id', $courseId)->first();
    }
}
