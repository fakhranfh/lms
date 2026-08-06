<?php

namespace App\Repositories\ForumComment;

use App\Models\ForumComment;
use Illuminate\Database\Eloquent\Collection;

class ForumCommentRepository implements ForumCommentRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ForumComment::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->oldest()->get();
    }

    public function find(string $id, array $with = []): ?ForumComment
    {
        return ForumComment::with($with)->find($id);
    }

    public function create(array $data): ForumComment
    {
        return ForumComment::create($data);
    }

    public function update(string $id, array $data): ForumComment
    {
        $model = ForumComment::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ForumComment::destroy($id);
    }

    public function incrementLikesCount(string $id): void
    {
        ForumComment::whereKey($id)->increment('likes_count');
    }

    public function decrementLikesCount(string $id): void
    {
        ForumComment::whereKey($id)->decrement('likes_count');
    }

    /**
     * @return Collection<int, ForumComment>
     */
    public function topLevelForThread(string $threadId, array $with = []): Collection
    {
        return ForumComment::where('thread_id', $threadId)
            ->whereNull('parent_id')
            ->with($with)
            ->oldest()
            ->get();
    }
}
