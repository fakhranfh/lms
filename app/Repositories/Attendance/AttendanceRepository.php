<?php

namespace App\Repositories\Attendance;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Attendance::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Attendance
    {
        return Attendance::with($with)->find($id);
    }

    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function update(string $id, array $data): Attendance
    {
        $model = Attendance::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Attendance::destroy($id);
    }

    public function findBySessionAndUser(string $sessionId, string $userId): ?Attendance
    {
        return Attendance::where('session_id', $sessionId)->where('user_id', $userId)->first();
    }

    public function forUser(string $userId): Collection
    {
        return Attendance::where('user_id', $userId)->with('session')->get();
    }

    public function deleteForCourse(string $courseId): int
    {
        return Attendance::whereHas('session', fn ($query) => $query->where('course_id', $courseId))->delete();
    }
}
