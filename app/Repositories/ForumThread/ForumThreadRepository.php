<?php

namespace App\Repositories\ForumThread;

use App\Models\ForumThread;
use Illuminate\Database\Eloquent\Collection;

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

    public function decrementCommentsCount(string $id): void
    {
        ForumThread::whereKey($id)->decrement('comments_count');
    }
}
