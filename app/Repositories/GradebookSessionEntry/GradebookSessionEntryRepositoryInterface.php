<?php

namespace App\Repositories\GradebookSessionEntry;

use App\Models\GradebookSessionEntry;
use Illuminate\Database\Eloquent\Collection;

interface GradebookSessionEntryRepositoryInterface
{
    /**
     * @return Collection<int, GradebookSessionEntry>
     */
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?GradebookSessionEntry;

    public function create(array $data): GradebookSessionEntry;

    public function update(string $id, array $data): GradebookSessionEntry;

    public function delete(string $id): int;

    /**
     * @return Collection<int, GradebookSessionEntry>
     */
    public function forGradebookEntry(string $gradebookEntryId): Collection;
}
