<?php

namespace App\Repositories\SessionSubtopic;

use App\Models\SessionSubtopic;
use Illuminate\Database\Eloquent\Collection;

interface SessionSubtopicRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?SessionSubtopic;

    public function create(array $data): SessionSubtopic;

    public function update(string $id, array $data): SessionSubtopic;

    public function delete(string $id): int;
}
