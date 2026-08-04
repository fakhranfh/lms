<?php

namespace App\Repositories\AssessmentQuizAnswer;

use App\Models\AssessmentQuizAnswer;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuizAnswerRepository implements AssessmentQuizAnswerRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentQuizAnswer::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?AssessmentQuizAnswer
    {
        return AssessmentQuizAnswer::with($with)->find($id);
    }

    public function create(array $data): AssessmentQuizAnswer
    {
        return AssessmentQuizAnswer::create($data);
    }

    public function update(string $id, array $data): AssessmentQuizAnswer
    {
        $model = AssessmentQuizAnswer::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentQuizAnswer::destroy($id);
    }

    public function forAttempt(string $attemptId): Collection
    {
        return AssessmentQuizAnswer::where('assessment_attempt_id', $attemptId)->get();
    }
}
