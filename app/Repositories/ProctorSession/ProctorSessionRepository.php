<?php

namespace App\Repositories\ProctorSession;

use App\Models\ProctorSession;
use Illuminate\Database\Eloquent\Collection;

class ProctorSessionRepository implements ProctorSessionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ProctorSession::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?ProctorSession
    {
        return ProctorSession::with($with)->find($id);
    }

    public function create(array $data): ProctorSession
    {
        return ProctorSession::create($data);
    }

    public function update(string $id, array $data): ProctorSession
    {
        $model = ProctorSession::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ProctorSession::destroy($id);
    }

    public function findByAttempt(string $attemptId, array $with = []): ?ProctorSession
    {
        return ProctorSession::with($with)->where('assessment_attempt_id', $attemptId)->first();
    }
}
