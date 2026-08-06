<?php

namespace App\Services;

use App\Models\ForumThread;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ForumThreadService
{
    public function __construct(
        private ForumThreadRepositoryInterface $forumThreadRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->forumThreadRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ForumThread
    {
        return $this->forumThreadRepository->find($id, $with);
    }

    public function create(array $data): ForumThread
    {
        return $this->forumThreadRepository->create($data);
    }

    public function update(string $id, array $data): ForumThread
    {
        return $this->forumThreadRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->forumThreadRepository->delete($id);
    }

    public function paginateForForum(string $forumId, int $perPage, int $page, array $with = []): LengthAwarePaginator
    {
        return $this->forumThreadRepository->paginateForForum($forumId, $perPage, $page, $with);
    }

    /**
     * @return array{threads: int, comments: int}
     */
    public function totalPostsForForum(string $forumId): array
    {
        return $this->forumThreadRepository->totalPostsForForum($forumId);
    }

    public function myPostsCountForForum(string $forumId, string $userId): int
    {
        return $this->forumThreadRepository->myPostsCountForForum($forumId, $userId);
    }
}
