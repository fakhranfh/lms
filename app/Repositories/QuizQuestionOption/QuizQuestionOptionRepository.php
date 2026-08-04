<?php

namespace App\Repositories\QuizQuestionOption;

use App\Models\QuizQuestionOption;
use Illuminate\Database\Eloquent\Collection;

class QuizQuestionOptionRepository implements QuizQuestionOptionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = QuizQuestionOption::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?QuizQuestionOption
    {
        return QuizQuestionOption::with($with)->find($id);
    }

    public function create(array $data): QuizQuestionOption
    {
        return QuizQuestionOption::create($data);
    }

    public function update(string $id, array $data): QuizQuestionOption
    {
        $model = QuizQuestionOption::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return QuizQuestionOption::destroy($id);
    }
}
