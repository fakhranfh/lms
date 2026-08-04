<?php

namespace App\Repositories\GroupMember;

use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Collection;

interface GroupMemberRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?GroupMember;

    public function create(array $data): GroupMember;

    public function update(string $id, array $data): GroupMember;

    public function delete(string $id): int;

    public function findByGroupAndUser(string $groupId, string $userId): ?GroupMember;
}
