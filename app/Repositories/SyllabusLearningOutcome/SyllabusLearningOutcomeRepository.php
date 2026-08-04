<?php

namespace App\Repositories\SyllabusLearningOutcome;

use App\Models\SyllabusLearningOutcome;
use Illuminate\Database\Eloquent\Collection;

class SyllabusLearningOutcomeRepository implements SyllabusLearningOutcomeRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusLearningOutcome::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SyllabusLearningOutcome
    {
        return SyllabusLearningOutcome::with($with)->find($id);
    }

    public function create(array $data): SyllabusLearningOutcome
    {
        return SyllabusLearningOutcome::create($data);
    }

    public function update(string $id, array $data): SyllabusLearningOutcome
    {
        $model = SyllabusLearningOutcome::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusLearningOutcome::destroy($id);
    }
}
