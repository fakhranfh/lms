<?php

namespace App\Repositories\AssessmentQuestion;

use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionRepository implements AssessmentQuestionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentQuestion::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?AssessmentQuestion
    {
        return AssessmentQuestion::with($with)->find($id);
    }

    public function create(array $data): AssessmentQuestion
    {
        return AssessmentQuestion::create($data);
    }

    public function update(string $id, array $data): AssessmentQuestion
    {
        $model = AssessmentQuestion::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentQuestion::destroy($id);
    }
}
