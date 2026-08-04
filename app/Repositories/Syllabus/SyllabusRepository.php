<?php

namespace App\Repositories\Syllabus;

use App\Models\Syllabus;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRepository implements SyllabusRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Syllabus::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Syllabus
    {
        return Syllabus::with($with)->find($id);
    }

    public function create(array $data): Syllabus
    {
        return Syllabus::create($data);
    }

    public function update(string $id, array $data): Syllabus
    {
        $model = Syllabus::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Syllabus::destroy($id);
    }

    public function findByCourse(string $courseId, array $with = []): ?Syllabus
    {
        return Syllabus::with($with)->where('course_id', $courseId)->first();
    }
}
