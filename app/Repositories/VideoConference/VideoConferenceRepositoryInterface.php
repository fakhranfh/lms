<?php

namespace App\Repositories\VideoConference;

use App\Models\VideoConference;
use Illuminate\Database\Eloquent\Collection;

interface VideoConferenceRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?VideoConference;

    public function create(array $data): VideoConference;

    public function update(string $id, array $data): VideoConference;

    public function delete(string $id): int;
}
