<?php

namespace App\Repositories\SyllabusEvaluation;

use App\Models\SyllabusEvaluation;
use Illuminate\Database\Eloquent\Collection;

class SyllabusEvaluationRepository implements SyllabusEvaluationRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusEvaluation::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SyllabusEvaluation
    {
        return SyllabusEvaluation::with($with)->find($id);
    }

    public function create(array $data): SyllabusEvaluation
    {
        return SyllabusEvaluation::create($data);
    }

    public function update(string $id, array $data): SyllabusEvaluation
    {
        $model = SyllabusEvaluation::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusEvaluation::destroy($id);
    }
}
