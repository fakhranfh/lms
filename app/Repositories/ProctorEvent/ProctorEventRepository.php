<?php

namespace App\Repositories\ProctorEvent;

use App\Models\ProctorEvent;
use Illuminate\Database\Eloquent\Collection;

class ProctorEventRepository implements ProctorEventRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ProctorEvent::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?ProctorEvent
    {
        return ProctorEvent::with($with)->find($id);
    }

    public function create(array $data): ProctorEvent
    {
        return ProctorEvent::create($data);
    }

    public function update(string $id, array $data): ProctorEvent
    {
        $model = ProctorEvent::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ProctorEvent::destroy($id);
    }

    public function forSession(string $proctorSessionId): Collection
    {
        return ProctorEvent::where('proctor_session_id', $proctorSessionId)->orderBy('detected_at')->get();
    }
}
