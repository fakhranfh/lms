<?php

namespace App\Repositories\SyllabusEvaluationActivity;

use App\Models\SyllabusEvaluationActivity;
use Illuminate\Database\Eloquent\Collection;

class SyllabusEvaluationActivityRepository implements SyllabusEvaluationActivityRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusEvaluationActivity::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SyllabusEvaluationActivity
    {
        return SyllabusEvaluationActivity::with($with)->find($id);
    }

    public function create(array $data): SyllabusEvaluationActivity
    {
        return SyllabusEvaluationActivity::create($data);
    }

    public function update(string $id, array $data): SyllabusEvaluationActivity
    {
        $model = SyllabusEvaluationActivity::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusEvaluationActivity::destroy($id);
    }

    public function syncLearningOutcomes(string $id, array $learningOutcomeIds): void
    {
        SyllabusEvaluationActivity::findOrFail($id)->learningOutcomes()->sync($learningOutcomeIds);
    }
}
