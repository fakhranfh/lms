<?php

namespace App\Services;

use App\Models\ForumCommentLike;
use App\Repositories\ForumComment\ForumCommentRepositoryInterface;
use App\Repositories\ForumCommentLike\ForumCommentLikeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ForumCommentLikeService
{
    public function __construct(
        private ForumCommentLikeRepositoryInterface $forumCommentLikeRepository,
        private ForumCommentRepositoryInterface $forumCommentRepository,
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->forumCommentLikeRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ForumCommentLike
    {
        return $this->forumCommentLikeRepository->find($id, $with);
    }

    public function create(array $data): ForumCommentLike
    {
        return DB::transaction(function () use ($data) {
            $like = $this->forumCommentLikeRepository->create($data);
            $this->forumCommentRepository->incrementLikesCount($data['comment_id']);

            return $like;
        });
    }

    public function delete(string $id): int
    {
        $like = $this->forumCommentLikeRepository->find($id);

        return DB::transaction(function () use ($id, $like) {
            $deleted = $this->forumCommentLikeRepository->delete($id);

            if ($like) {
                $this->forumCommentRepository->decrementLikesCount($like->comment_id);
            }

            return $deleted;
        });
    }

    public function findByCommentAndUser(string $commentId, string $userId): ?ForumCommentLike
    {
        return $this->forumCommentLikeRepository->findByCommentAndUser($commentId, $userId);
    }
}
