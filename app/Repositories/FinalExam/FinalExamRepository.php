<?php

namespace App\Repositories\FinalExam;

use App\Models\FinalExam;
use Illuminate\Database\Eloquent\Collection;

class FinalExamRepository implements FinalExamRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = FinalExam::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?FinalExam
    {
        return FinalExam::with($with)->find($id);
    }

    public function create(array $data): FinalExam
    {
        return FinalExam::create($data);
    }

    public function update(string $id, array $data): FinalExam
    {
        $model = FinalExam::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return FinalExam::destroy($id);
    }

    public function findByAssessment(string $assessmentId, array $with = []): ?FinalExam
    {
        return FinalExam::with($with)->where('assessment_id', $assessmentId)->first();
    }
}
