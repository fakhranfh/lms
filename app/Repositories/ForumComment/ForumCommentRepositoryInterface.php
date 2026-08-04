<?php

namespace App\Repositories\ForumComment;

use App\Models\ForumComment;
use Illuminate\Database\Eloquent\Collection;

interface ForumCommentRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ForumComment;

    public function create(array $data): ForumComment;

    public function update(string $id, array $data): ForumComment;

    public function delete(string $id): int;

    public function incrementLikesCount(string $id): void;

    public function decrementLikesCount(string $id): void;
}
