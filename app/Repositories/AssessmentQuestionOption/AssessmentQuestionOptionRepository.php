<?php

namespace App\Repositories\AssessmentQuestionOption;

use App\Models\AssessmentQuestionOption;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionOptionRepository implements AssessmentQuestionOptionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentQuestionOption::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?AssessmentQuestionOption
    {
        return AssessmentQuestionOption::with($with)->find($id);
    }

    public function create(array $data): AssessmentQuestionOption
    {
        return AssessmentQuestionOption::create($data);
    }

    public function update(string $id, array $data): AssessmentQuestionOption
    {
        $model = AssessmentQuestionOption::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentQuestionOption::destroy($id);
    }
}
