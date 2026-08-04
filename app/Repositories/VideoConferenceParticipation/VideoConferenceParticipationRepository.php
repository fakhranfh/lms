<?php

namespace App\Repositories\VideoConferenceParticipation;

use App\Models\VideoConferenceParticipation;
use Illuminate\Database\Eloquent\Collection;

class VideoConferenceParticipationRepository implements VideoConferenceParticipationRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = VideoConferenceParticipation::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?VideoConferenceParticipation
    {
        return VideoConferenceParticipation::with($with)->find($id);
    }

    public function create(array $data): VideoConferenceParticipation
    {
        return VideoConferenceParticipation::create($data);
    }

    public function update(string $id, array $data): VideoConferenceParticipation
    {
        $model = VideoConferenceParticipation::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return VideoConferenceParticipation::destroy($id);
    }
}
