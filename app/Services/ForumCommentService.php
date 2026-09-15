<?php

namespace App\Services;

use App\Models\ForumComment;
use App\Repositories\ForumComment\ForumCommentRepositoryInterface;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ForumCommentService
{
    public function __construct(
        private ForumCommentRepositoryInterface $forumCommentRepository,
        private ForumThreadRepositoryInterface $forumThreadRepository,
        private RichTextAttachmentCleanupService $richTextAttachmentCleanupService,
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->forumCommentRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ForumComment
    {
        return $this->forumCommentRepository->find($id, $with);
    }

    public function create(array $data): ForumComment
    {
        return DB::transaction(function () use ($data) {
            $comment = $this->forumCommentRepository->create($data);
            $this->forumThreadRepository->incrementCommentsCount($data['thread_id']);

            return $comment;
        });
    }

    public function update(string $id, array $data): ForumComment
    {
        if (array_key_exists('body', $data)) {
            $existing = $this->forumCommentRepository->find($id);
            $this->richTextAttachmentCleanupService->deleteRemoved($existing?->body, $data['body']);
        }

        return $this->forumCommentRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        $comment = $this->forumCommentRepository->find($id, ['replies']);

        return DB::transaction(function () use ($id, $comment) {
            $deleted = $this->forumCommentRepository->delete($id);

            if ($comment) {
                $postsRemoved = 1 + $comment->replies->count();
                $this->forumThreadRepository->decrementCommentsCount($comment->thread_id, $postsRemoved);

                $this->richTextAttachmentCleanupService->deleteFromHtml($comment->body);
                foreach ($comment->replies as $reply) {
                    $this->richTextAttachmentCleanupService->deleteFromHtml($reply->body);
                }
            }

            return $deleted;
        });
    }

    /**
     * @return Collection<int, ForumComment>
     */
    public function topLevelForThread(string $threadId, array $with = []): Collection
    {
        return $this->forumCommentRepository->topLevelForThread($threadId, $with);
    }

    public function paginateTopLevelForThread(string $threadId, int $perPage, int $page, array $with = [], string $sortBy = 'latest_comment'): LengthAwarePaginator
    {
        return $this->forumCommentRepository->paginateTopLevelForThread($threadId, $perPage, $page, $with, $sortBy);
    }

    public function countForUserInSession(string $userId, string $sessionId): int
    {
        return $this->forumCommentRepository->countForUserInSession($userId, $sessionId);
    }

    /**
     * @return Collection<int, ForumComment>
     */
    public function forUserInSession(string $userId, string $sessionId, array $with = []): Collection
    {
        return $this->forumCommentRepository->forUserInSession($userId, $sessionId, $with);
    }

    /**
     * @return array<int, string>
     */
    public function orderedTopLevelIdsForThread(string $threadId, string $sortBy): array
    {
        return $this->forumCommentRepository->orderedTopLevelIdsForThread($threadId, $sortBy);
    }
}
