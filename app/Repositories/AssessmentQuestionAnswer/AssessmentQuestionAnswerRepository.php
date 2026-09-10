<?php

namespace App\Repositories\AssessmentQuestionAnswer;

use App\Models\AssessmentQuestionAnswer;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionAnswerRepository implements AssessmentQuestionAnswerRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentQuestionAnswer::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?AssessmentQuestionAnswer
    {
        return AssessmentQuestionAnswer::with($with)->find($id);
    }

    public function create(array $data): AssessmentQuestionAnswer
    {
        return AssessmentQuestionAnswer::create($data);
    }

    public function update(string $id, array $data): AssessmentQuestionAnswer
    {
        $model = AssessmentQuestionAnswer::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentQuestionAnswer::destroy($id);
    }

    public function forAttempt(string $attemptId): Collection
    {
        return AssessmentQuestionAnswer::where('assessment_attempt_id', $attemptId)->get();
    }
}
