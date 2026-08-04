<?php

namespace App\Services;

use App\Models\ForumThread;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
}
