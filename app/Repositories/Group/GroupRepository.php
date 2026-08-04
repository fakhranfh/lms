<?php

namespace App\Repositories\Group;

use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class GroupRepository implements GroupRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Group::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Group
    {
        return Group::with($with)->find($id);
    }

    public function create(array $data): Group
    {
        return Group::create($data);
    }

    public function update(string $id, array $data): Group
    {
        $model = Group::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Group::destroy($id);
    }

    public function forCourse(string $courseId): Collection
    {
        return Group::where('course_id', $courseId)->with('members.user')->get();
    }
}
