<?php

namespace App\Repositories\Quiz;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;

class QuizRepository implements QuizRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Quiz::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Quiz
    {
        return Quiz::with($with)->find($id);
    }

    public function create(array $data): Quiz
    {
        return Quiz::create($data);
    }

    public function update(string $id, array $data): Quiz
    {
        $model = Quiz::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Quiz::destroy($id);
    }

    public function findByAssessment(string $assessmentId, array $with = []): ?Quiz
    {
        return Quiz::with($with)->where('assessment_id', $assessmentId)->first();
    }
}
