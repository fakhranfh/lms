<?php

namespace App\Services;

use App\Models\VideoConference;
use App\Repositories\VideoConference\VideoConferenceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VideoConferenceService
{
    public function __construct(
        private VideoConferenceRepositoryInterface $videoConferenceRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->videoConferenceRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?VideoConference
    {
        return $this->videoConferenceRepository->find($id, $with);
    }

    public function create(array $data): VideoConference
    {
        return $this->videoConferenceRepository->create($data);
    }

    public function update(string $id, array $data): VideoConference
    {
        return $this->videoConferenceRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->videoConferenceRepository->delete($id);
    }
}
