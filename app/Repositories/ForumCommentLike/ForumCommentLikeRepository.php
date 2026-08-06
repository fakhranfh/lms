<?php

namespace App\Repositories\ForumCommentLike;

use App\Models\ForumCommentLike;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class ForumCommentLikeRepository implements ForumCommentLikeRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ForumCommentLike::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?ForumCommentLike
    {
        return ForumCommentLike::with($with)->find($id);
    }

    public function create(array $data): ForumCommentLike
    {
        return ForumCommentLike::create($data);
    }

    public function update(string $id, array $data): ForumCommentLike
    {
        $model = ForumCommentLike::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ForumCommentLike::destroy($id);
    }

    public function findByCommentAndUser(string $commentId, string $userId): ?ForumCommentLike
    {
        return ForumCommentLike::where('comment_id', $commentId)->where('user_id', $userId)->first();
    }

    public function likedCommentIdsForUser(array $commentIds, string $userId): BaseCollection
    {
        return ForumCommentLike::where('user_id', $userId)->whereIn('comment_id', $commentIds)->pluck('comment_id');
    }
}
