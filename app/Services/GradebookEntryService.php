<?php

namespace App\Services;

use App\Models\GradebookEntry;
use App\Repositories\GradebookEntry\GradebookEntryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GradebookEntryService
{
    public function __construct(
        private GradebookEntryRepositoryInterface $gradebookEntryRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->gradebookEntryRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?GradebookEntry
    {
        return $this->gradebookEntryRepository->find($id, $with);
    }

    public function create(array $data): GradebookEntry
    {
        return $this->gradebookEntryRepository->create($data);
    }

    public function update(string $id, array $data): GradebookEntry
    {
        return $this->gradebookEntryRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->gradebookEntryRepository->delete($id);
    }

    public function forCourseAndUser(string $courseId, string $userId): Collection
    {
        return $this->gradebookEntryRepository->forCourseAndUser($courseId, $userId);
    }
}
