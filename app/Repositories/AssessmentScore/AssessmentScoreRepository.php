<?php

namespace App\Repositories\AssessmentScore;

use App\Models\AssessmentScore;
use Illuminate\Database\Eloquent\Collection;

class AssessmentScoreRepository implements AssessmentScoreRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentScore::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?AssessmentScore
    {
        return AssessmentScore::with($with)->find($id);
    }

    public function create(array $data): AssessmentScore
    {
        return AssessmentScore::create($data);
    }

    public function update(string $id, array $data): AssessmentScore
    {
        $model = AssessmentScore::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentScore::destroy($id);
    }

    public function findByAttempt(string $attemptId): ?AssessmentScore
    {
        return AssessmentScore::where('assessment_attempt_id', $attemptId)->first();
    }
}
