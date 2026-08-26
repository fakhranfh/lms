<?php

namespace App\Repositories\GradebookGradeScale;

use App\Models\GradebookGradeScale;
use Illuminate\Database\Eloquent\Collection;

class GradebookGradeScaleRepository implements GradebookGradeScaleRepositoryInterface
{
    /**
     * @return Collection<int, GradebookGradeScale>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = GradebookGradeScale::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?GradebookGradeScale
    {
        return GradebookGradeScale::with($with)->find($id);
    }

    public function create(array $data): GradebookGradeScale
    {
        return GradebookGradeScale::create($data);
    }

    public function update(string $id, array $data): GradebookGradeScale
    {
        $model = GradebookGradeScale::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return GradebookGradeScale::destroy($id);
    }

    /**
     * @return Collection<int, GradebookGradeScale>
     */
    public function forCourseOrDefault(?string $courseId): Collection
    {
        $courseScales = $courseId
            ? GradebookGradeScale::where('course_id', $courseId)->orderBy('order')->get()
            : new Collection;

        return $courseScales->isNotEmpty()
            ? $courseScales
            : GradebookGradeScale::whereNull('course_id')->orderBy('order')->get();
    }
}
