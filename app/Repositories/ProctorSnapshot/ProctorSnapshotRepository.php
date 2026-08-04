<?php

namespace App\Repositories\ProctorSnapshot;

use App\Models\ProctorSnapshot;
use Illuminate\Database\Eloquent\Collection;

class ProctorSnapshotRepository implements ProctorSnapshotRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ProctorSnapshot::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?ProctorSnapshot
    {
        return ProctorSnapshot::with($with)->find($id);
    }

    public function create(array $data): ProctorSnapshot
    {
        return ProctorSnapshot::create($data);
    }

    public function update(string $id, array $data): ProctorSnapshot
    {
        $model = ProctorSnapshot::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ProctorSnapshot::destroy($id);
    }

    public function forSession(string $proctorSessionId): Collection
    {
        return ProctorSnapshot::where('proctor_session_id', $proctorSessionId)->orderBy('captured_at')->get();
    }
}
