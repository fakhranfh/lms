<?php

namespace App\Repositories\ForumCommentLike;

use App\Models\ForumCommentLike;
use Illuminate\Database\Eloquent\Collection;

interface ForumCommentLikeRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ForumCommentLike;

    public function create(array $data): ForumCommentLike;

    public function update(string $id, array $data): ForumCommentLike;

    public function delete(string $id): int;

    public function findByCommentAndUser(string $commentId, string $userId): ?ForumCommentLike;
}
