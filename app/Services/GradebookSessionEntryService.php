<?php

namespace App\Services;

use App\Models\GradebookSessionEntry;
use App\Repositories\GradebookSessionEntry\GradebookSessionEntryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GradebookSessionEntryService
{
    public function __construct(
        private GradebookSessionEntryRepositoryInterface $gradebookSessionEntryRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->gradebookSessionEntryRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?GradebookSessionEntry
    {
        return $this->gradebookSessionEntryRepository->find($id, $with);
    }

    public function create(array $data): GradebookSessionEntry
    {
        return $this->gradebookSessionEntryRepository->create($data);
    }

    public function update(string $id, array $data): GradebookSessionEntry
    {
        return $this->gradebookSessionEntryRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->gradebookSessionEntryRepository->delete($id);
    }

    public function forGradebookEntry(string $gradebookEntryId): Collection
    {
        return $this->gradebookSessionEntryRepository->forGradebookEntry($gradebookEntryId);
    }
}
