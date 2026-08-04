<?php

namespace App\Services;

use App\Models\Group;
use App\Repositories\Group\GroupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GroupService
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->groupRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Group
    {
        return $this->groupRepository->find($id, $with);
    }

    public function create(array $data): Group
    {
        return $this->groupRepository->create($data);
    }

    public function update(string $id, array $data): Group
    {
        return $this->groupRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->groupRepository->delete($id);
    }

    public function forCourse(string $courseId): Collection
    {
        return $this->groupRepository->forCourse($courseId);
    }
}
