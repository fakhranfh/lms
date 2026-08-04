<?php

namespace App\Repositories\Forum;

use App\Models\Forum;
use Illuminate\Database\Eloquent\Collection;

interface ForumRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Forum;

    public function create(array $data): Forum;

    public function update(string $id, array $data): Forum;

    public function delete(string $id): int;
}
