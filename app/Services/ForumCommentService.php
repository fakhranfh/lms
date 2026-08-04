<?php

namespace App\Services;

use App\Models\ForumComment;
use App\Repositories\ForumComment\ForumCommentRepositoryInterface;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ForumCommentService
{
    public function __construct(
        private ForumCommentRepositoryInterface $forumCommentRepository,
        private ForumThreadRepositoryInterface $forumThreadRepository,
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
        return $this->forumCommentRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        $comment = $this->forumCommentRepository->find($id);

        return DB::transaction(function () use ($id, $comment) {
            $deleted = $this->forumCommentRepository->delete($id);

            if ($comment) {
                $this->forumThreadRepository->decrementCommentsCount($comment->thread_id);
            }

            return $deleted;
        });
    }
}
