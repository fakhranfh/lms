<?php

namespace App\Repositories\ForumComment;

use App\Models\ForumComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function paginateTopLevelForThread(string $threadId, int $perPage, int $page, array $with = [], string $sortBy = 'latest_comment'): LengthAwarePaginator
    {
        $query = ForumComment::where('thread_id', $threadId)
            ->whereNull('parent_id')
            ->with($with);

        $this->applyTopLevelSort($query, $sortBy);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function orderedTopLevelIdsForThread(string $threadId, string $sortBy): array
    {
        $query = ForumComment::where('thread_id', $threadId)->whereNull('parent_id');

        $this->applyTopLevelSort($query, $sortBy);

        return $query->pluck('id')->all();
    }

    private function applyTopLevelSort(Builder $query, string $sortBy): void
    {
        match ($sortBy) {
            'oldest_comment' => $query->oldest(),
            'most_liked_comment' => $query->orderByDesc('likes_count'),
            'latest_reply' => $query->orderByRaw(
                'COALESCE((select max(created_at) from forum_comments as replies where replies.parent_id = forum_comments.id), created_at) desc'
            ),
            'oldest_reply' => $query->orderByRaw(
                'COALESCE((select min(created_at) from forum_comments as replies where replies.parent_id = forum_comments.id), created_at) asc'
            ),
            'most_liked_reply' => $query->orderByRaw(
                'COALESCE((select max(likes_count) from forum_comments as replies where replies.parent_id = forum_comments.id), 0) desc'
            ),
            default => $query->latest(),
        };
    }

    public function countForUserInSession(string $userId, string $sessionId): int
    {
        return ForumComment::where('user_id', $userId)
            ->whereHas('thread.forum', fn ($query) => $query->where('session_id', $sessionId))
            ->count();
    }

    public function forUserInSession(string $userId, string $sessionId, array $with = []): Collection
    {
        return ForumComment::where('user_id', $userId)
            ->whereHas('thread.forum', fn ($query) => $query->where('session_id', $sessionId))
            ->with($with)
            ->latest()
            ->get();
    }
}
