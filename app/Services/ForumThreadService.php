<?php

namespace App\Services;

use App\Models\ForumThread;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ForumThreadService
{
    public function __construct(
        private ForumThreadRepositoryInterface $forumThreadRepository,
        private RichTextAttachmentCleanupService $richTextAttachmentCleanupService,
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
        if (array_key_exists('description', $data)) {
            $existing = $this->forumThreadRepository->find($id);
            $this->richTextAttachmentCleanupService->deleteRemoved($existing?->description, $data['description']);
        }

        return $this->forumThreadRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        $thread = $this->forumThreadRepository->find($id, ['comments']);

        if ($thread) {
            $this->richTextAttachmentCleanupService->deleteFromHtml($thread->description);

            foreach ($thread->comments as $comment) {
                $this->richTextAttachmentCleanupService->deleteFromHtml($comment->body);
            }
        }

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

    public function countForUserInSession(string $userId, string $sessionId): int
    {
        return $this->forumThreadRepository->countForUserInSession($userId, $sessionId);
    }

    /**
     * @return Collection<int, ForumThread>
     */
    public function forUserInSession(string $userId, string $sessionId, array $with = []): Collection
    {
        return $this->forumThreadRepository->forUserInSession($userId, $sessionId, $with);
    }
}
