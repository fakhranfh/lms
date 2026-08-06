<?php

namespace App\Repositories\ForumThreadRead;

use App\Models\ForumThreadRead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class ForumThreadReadRepository implements ForumThreadReadRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ForumThreadRead::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?ForumThreadRead
    {
        return ForumThreadRead::with($with)->find($id);
    }

    public function create(array $data): ForumThreadRead
    {
        return ForumThreadRead::create($data);
    }

    public function update(string $id, array $data): ForumThreadRead
    {
        $model = ForumThreadRead::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ForumThreadRead::destroy($id);
    }

    public function markRead(string $threadId, string $userId): ForumThreadRead
    {
        return ForumThreadRead::updateOrCreate(
            ['thread_id' => $threadId, 'user_id' => $userId],
            ['read_at' => now()]
        );
    }

    public function readAtByThreadForUser(array $threadIds, string $userId): BaseCollection
    {
        return ForumThreadRead::where('user_id', $userId)
            ->whereIn('thread_id', $threadIds)
            ->pluck('read_at', 'thread_id');
    }
}
