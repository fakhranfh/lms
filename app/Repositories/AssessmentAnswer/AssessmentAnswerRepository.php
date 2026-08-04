<?php

namespace App\Repositories\AssessmentAnswer;

use App\Models\AssessmentAnswer;
use Illuminate\Database\Eloquent\Collection;

class AssessmentAnswerRepository implements AssessmentAnswerRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentAnswer::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?AssessmentAnswer
    {
        return AssessmentAnswer::with($with)->find($id);
    }

    public function create(array $data): AssessmentAnswer
    {
        return AssessmentAnswer::create($data);
    }

    public function update(string $id, array $data): AssessmentAnswer
    {
        $model = AssessmentAnswer::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentAnswer::destroy($id);
    }

    public function findByAttempt(string $attemptId): ?AssessmentAnswer
    {
        return AssessmentAnswer::where('assessment_attempt_id', $attemptId)->first();
    }
}
