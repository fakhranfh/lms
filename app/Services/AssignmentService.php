<?php

namespace App\Services;

use App\Models\Assignment;
use App\Repositories\Assignment\AssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssignmentService
{
    public function __construct(
        private AssignmentRepositoryInterface $assignmentRepository
    ) {}

    /**
     * Get assignments with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assignmentRepository->get($filters, $with);
    }

    /**
     * Find an assignment by ID.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Assignment
    {
        return $this->assignmentRepository->find($id, $with);
    }

    /**
     * Get all assignments for a lesson.
     *
     * @param  array<string>  $with
     */
    public function getByLesson(string $lessonId, array $with = []): Collection
    {
        return $this->assignmentRepository->getByLesson($lessonId, $with);
    }

    /**
     * Create a new assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Assignment
    {
        return $this->assignmentRepository->create($data);
    }

    /**
     * Update an assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Assignment
    {
        return $this->assignmentRepository->update($id, $data);
    }

    /**
     * Delete an assignment.
     */
    public function delete(string $id): int
    {
        return $this->assignmentRepository->delete($id);
    }

    /**
     * Publish an assignment.
     */
    public function publish(string $id): Assignment
    {
        return $this->assignmentRepository->update($id, ['is_published' => true]);
    }

    /**
     * Unpublish an assignment.
     */
    public function unpublish(string $id): Assignment
    {
        return $this->assignmentRepository->update($id, ['is_published' => false]);
    }
}
