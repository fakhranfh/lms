<?php

namespace App\Repositories\SessionSubtopic;

use App\Models\SessionSubtopic;
use Illuminate\Database\Eloquent\Collection;

class SessionSubtopicRepository implements SessionSubtopicRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SessionSubtopic::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SessionSubtopic
    {
        return SessionSubtopic::with($with)->find($id);
    }

    public function create(array $data): SessionSubtopic
    {
        return SessionSubtopic::create($data);
    }

    public function update(string $id, array $data): SessionSubtopic
    {
        $model = SessionSubtopic::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SessionSubtopic::destroy($id);
    }
}
