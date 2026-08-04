<?php

namespace App\Services;

use App\Models\VideoConferenceParticipation;
use App\Repositories\VideoConferenceParticipation\VideoConferenceParticipationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VideoConferenceParticipationService
{
    public function __construct(
        private VideoConferenceParticipationRepositoryInterface $videoConferenceParticipationRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->videoConferenceParticipationRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?VideoConferenceParticipation
    {
        return $this->videoConferenceParticipationRepository->find($id, $with);
    }

    public function create(array $data): VideoConferenceParticipation
    {
        return $this->videoConferenceParticipationRepository->create($data);
    }

    public function update(string $id, array $data): VideoConferenceParticipation
    {
        return $this->videoConferenceParticipationRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->videoConferenceParticipationRepository->delete($id);
    }
}
