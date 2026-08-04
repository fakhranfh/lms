<?php

namespace App\Repositories\AssessmentAttempt;

use App\Models\AssessmentAttempt;
use Illuminate\Database\Eloquent\Collection;

class AssessmentAttemptRepository implements AssessmentAttemptRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = AssessmentAttempt::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?AssessmentAttempt
    {
        return AssessmentAttempt::with($with)->find($id);
    }

    public function create(array $data): AssessmentAttempt
    {
        return AssessmentAttempt::create($data);
    }

    public function update(string $id, array $data): AssessmentAttempt
    {
        $model = AssessmentAttempt::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return AssessmentAttempt::destroy($id);
    }

    public function forAssessmentAndUser(string $assessmentId, string $userId): Collection
    {
        return AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->orderBy('attempt_number')
            ->get();
    }

    public function forAssessmentAndGroup(string $assessmentId, string $groupId): Collection
    {
        return AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('group_id', $groupId)
            ->orderBy('attempt_number')
            ->get();
    }
}
