<?php

namespace App\Repositories\AssessmentQuestionScore;

use App\Models\AssessmentQuestionScore;
use Illuminate\Database\Eloquent\Collection;

class AssessmentQuestionScoreRepository implements AssessmentQuestionScoreRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentQuestionScore::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?AssessmentQuestionScore
    {
        return AssessmentQuestionScore::with($with)->find($id);
    }

    public function create(array $data): AssessmentQuestionScore
    {
        return AssessmentQuestionScore::create($data);
    }

    public function update(string $id, array $data): AssessmentQuestionScore
    {
        $model = AssessmentQuestionScore::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentQuestionScore::destroy($id);
    }

    public function findByAttempt(string $attemptId, array $with = []): Collection
    {
        return AssessmentQuestionScore::where('assessment_attempt_id', $attemptId)
            ->with($with)
            ->get();
    }

    public function updateOrCreate(array $attributes, array $values): AssessmentQuestionScore
    {
        return AssessmentQuestionScore::updateOrCreate($attributes, $values);
    }
}
