<?php

namespace App\Repositories\ForumThread;

use App\Models\ForumThread;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ForumThreadRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ForumThread;

    public function create(array $data): ForumThread;

    public function update(string $id, array $data): ForumThread;

    public function delete(string $id): int;

    public function incrementCommentsCount(string $id): void;

    public function decrementCommentsCount(string $id, int $by = 1): void;

    /**
     * @param  array<int, string>  $forumIds
     */
    public function forForums(array $forumIds, array $with = []): Collection;

    public function paginateForForum(string $forumId, int $perPage, int $page, array $with = []): LengthAwarePaginator;

    /**
     * @return array{threads: int, comments: int}
     */
    public function totalPostsForForum(string $forumId): array;
}
