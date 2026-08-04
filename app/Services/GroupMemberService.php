<?php

namespace App\Services;

use App\Models\GroupMember;
use App\Repositories\GroupMember\GroupMemberRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GroupMemberService
{
    public function __construct(
        private GroupMemberRepositoryInterface $groupMemberRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->groupMemberRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?GroupMember
    {
        return $this->groupMemberRepository->find($id, $with);
    }

    public function create(array $data): GroupMember
    {
        return $this->groupMemberRepository->create($data);
    }

    public function update(string $id, array $data): GroupMember
    {
        return $this->groupMemberRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->groupMemberRepository->delete($id);
    }

    public function findByGroupAndUser(string $groupId, string $userId): ?GroupMember
    {
        return $this->groupMemberRepository->findByGroupAndUser($groupId, $userId);
    }
}
