<?php

namespace App\Repositories\ForumComment;

use App\Models\ForumComment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ForumCommentRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ForumComment;

    public function create(array $data): ForumComment;

    public function update(string $id, array $data): ForumComment;

    public function delete(string $id): int;

    public function incrementLikesCount(string $id): void;

    public function decrementLikesCount(string $id): void;

    /**
     * @return Collection<int, ForumComment>
     */
    public function topLevelForThread(string $threadId, array $with = []): Collection;

    public function paginateTopLevelForThread(string $threadId, int $perPage, int $page, array $with = [], string $sortBy = 'latest_comment'): LengthAwarePaginator;

    public function countForUserInSession(string $userId, string $sessionId): int;
}
