<?php

namespace App\Repositories\VideoConferenceParticipation;

use App\Models\VideoConferenceParticipation;
use Illuminate\Database\Eloquent\Collection;

interface VideoConferenceParticipationRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?VideoConferenceParticipation;

    public function create(array $data): VideoConferenceParticipation;

    public function update(string $id, array $data): VideoConferenceParticipation;

    public function delete(string $id): int;
}
