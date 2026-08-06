<?php

namespace App\Repositories\Forum;

use App\Models\Forum;
use Illuminate\Database\Eloquent\Collection;

class ForumRepository implements ForumRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Forum::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Forum
    {
        return Forum::with($with)->find($id);
    }

    public function create(array $data): Forum
    {
        return Forum::create($data);
    }

    public function update(string $id, array $data): Forum
    {
        $model = Forum::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Forum::destroy($id);
    }

    public function findBySessionAndCourse(string $sessionId, string $courseId): ?Forum
    {
        return Forum::where('course_id', $courseId)->where('session_id', $sessionId)->first();
    }
}
