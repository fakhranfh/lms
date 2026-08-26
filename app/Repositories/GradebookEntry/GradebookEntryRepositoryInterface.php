<?php

namespace App\Repositories\GradebookEntry;

use App\Models\GradebookEntry;
use Illuminate\Database\Eloquent\Collection;

interface GradebookEntryRepositoryInterface
{
    /**
     * @return Collection<int, GradebookEntry>
     */
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?GradebookEntry;

    public function create(array $data): GradebookEntry;

    public function update(string $id, array $data): GradebookEntry;

    public function delete(string $id): int;

    /**
     * @return Collection<int, GradebookEntry>
     */
    public function forCourseAndUser(string $courseId, string $userId): Collection;
}
