<?php

namespace App\Repositories\AttendanceRequirement;

use App\Models\AttendanceRequirement;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRequirementRepository implements AttendanceRequirementRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AttendanceRequirement::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?AttendanceRequirement
    {
        return AttendanceRequirement::with($with)->find($id);
    }

    public function create(array $data): AttendanceRequirement
    {
        return AttendanceRequirement::create($data);
    }

    public function update(string $id, array $data): AttendanceRequirement
    {
        $model = AttendanceRequirement::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AttendanceRequirement::destroy($id);
    }

    public function forCourse(string $courseId): Collection
    {
        return AttendanceRequirement::where('course_id', $courseId)->orderBy('order')->get();
    }
}
