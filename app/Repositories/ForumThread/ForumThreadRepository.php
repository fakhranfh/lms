<?php

namespace App\Repositories\ForumThread;

use App\Models\ForumThread;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ForumThreadRepository implements ForumThreadRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ForumThread::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->latest()->get();
    }

    public function find(string $id, array $with = []): ?ForumThread
    {
        return ForumThread::with($with)->find($id);
    }

    public function create(array $data): ForumThread
    {
        return ForumThread::create($data);
    }

    public function update(string $id, array $data): ForumThread
    {
        $model = ForumThread::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ForumThread::destroy($id);
    }

    public function incrementCommentsCount(string $id): void
    {
        ForumThread::whereKey($id)->increment('comments_count');
    }

    public function decrementCommentsCount(string $id, int $by = 1): void
    {
        ForumThread::whereKey($id)->decrement('comments_count', $by);
    }

    public function forForums(array $forumIds, array $with = []): Collection
    {
        return ForumThread::whereIn('forum_id', $forumIds)->with($with)->get();
    }

    public function paginateForForum(string $forumId, int $perPage, int $page, array $with = []): LengthAwarePaginator
    {
        return ForumThread::where('forum_id', $forumId)
            ->with($with)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function totalPostsForForum(string $forumId): array
    {
        $totals = ForumThread::where('forum_id', $forumId)
            ->toBase()
            ->selectRaw('count(*) as threads, coalesce(sum(comments_count), 0) as comments')
            ->first();

        return [
            'threads' => (int) ($totals->threads ?? 0),
            'comments' => (int) ($totals->comments ?? 0),
        ];
    }
}
