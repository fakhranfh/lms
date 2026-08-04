<?php

namespace App\Repositories\GroupMember;

use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Collection;

class GroupMemberRepository implements GroupMemberRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = GroupMember::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?GroupMember
    {
        return GroupMember::with($with)->find($id);
    }

    public function create(array $data): GroupMember
    {
        return GroupMember::create($data);
    }

    public function update(string $id, array $data): GroupMember
    {
        $model = GroupMember::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return GroupMember::destroy($id);
    }

    public function findByGroupAndUser(string $groupId, string $userId): ?GroupMember
    {
        return GroupMember::where('group_id', $groupId)->where('user_id', $userId)->first();
    }
}
