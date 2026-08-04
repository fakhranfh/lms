<?php

namespace App\Repositories\ForumThread;

use App\Models\ForumThread;
use Illuminate\Database\Eloquent\Collection;

interface ForumThreadRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ForumThread;

    public function create(array $data): ForumThread;

    public function update(string $id, array $data): ForumThread;

    public function delete(string $id): int;

    public function incrementCommentsCount(string $id): void;

    public function decrementCommentsCount(string $id): void;
}
