<?php

namespace App\Repositories\Assessment;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Collection;

class AssessmentRepository implements AssessmentRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Assessment::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Assessment
    {
        return Assessment::with($with)->find($id);
    }

    public function create(array $data): Assessment
    {
        return Assessment::create($data);
    }

    public function update(string $id, array $data): Assessment
    {
        $model = Assessment::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Assessment::destroy($id);
    }

    public function forCourse(string $courseId): Collection
    {
        return Assessment::where('course_id', $courseId)->get();
    }
}
