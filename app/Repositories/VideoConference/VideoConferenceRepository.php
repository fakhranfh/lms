<?php

namespace App\Repositories\VideoConference;

use App\Models\VideoConference;
use Illuminate\Database\Eloquent\Collection;

class VideoConferenceRepository implements VideoConferenceRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = VideoConference::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?VideoConference
    {
        return VideoConference::with($with)->find($id);
    }

    public function create(array $data): VideoConference
    {
        return VideoConference::create($data);
    }

    public function update(string $id, array $data): VideoConference
    {
        $model = VideoConference::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return VideoConference::destroy($id);
    }
}
