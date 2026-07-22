<?php

namespace App\Repositories\Assignment;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Collection;

interface AssignmentRepositoryInterface
{
    /**
     * Get assignments with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Find an assignment by ID with optional relations.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Assignment;

    /**
     * Get all assignments for a specific lesson.
     *
     * @param  array<string>  $with
     */
    public function getByLesson(string $lessonId, array $with = []): Collection;

    /**
     * Create a new assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Assignment;

    /**
     * Update an assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Assignment;

    /**
     * Delete an assignment.
     */
    public function delete(string $id): int;
}
