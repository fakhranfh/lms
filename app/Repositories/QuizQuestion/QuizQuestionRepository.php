<?php

namespace App\Repositories\QuizQuestion;

use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Collection;

class QuizQuestionRepository implements QuizQuestionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = QuizQuestion::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?QuizQuestion
    {
        return QuizQuestion::with($with)->find($id);
    }

    public function create(array $data): QuizQuestion
    {
        return QuizQuestion::create($data);
    }

    public function update(string $id, array $data): QuizQuestion
    {
        $model = QuizQuestion::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return QuizQuestion::destroy($id);
    }
}
