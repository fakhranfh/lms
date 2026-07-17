<?php

namespace App\Repositories\Lesson;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;

interface LessonRepositoryInterface
{
    /**
     * Get lessons with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Find a lesson by ID with optional relations.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Lesson;

    /**
     * Create a new lesson.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lesson;

    /**
     * Update a lesson.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Lesson;

    /**
     * Delete a lesson.
     */
    public function delete(string $id): int;

    /**
     * Get next order for a module.
     */
    public function getNextOrder(string $moduleId): int;

    /**
     * Move a lesson up in the order.
     */
    public function moveUp(string $id): void;

    /**
     * Move a lesson down in the order.
     */
    public function moveDown(string $id): void;

    /**
     * Publish a lesson.
     */
    public function publish(string $id): void;

    /**
     * Unpublish a lesson.
     */
    public function unpublish(string $id): void;

    /**
     * Mark lesson as completed by user.
     */
    public function markComplete(string $lessonId, string $userId): void;

    /**
     * Check if lesson is completed by user.
     */
    public function isCompletedBy(string $lessonId, string $userId): bool;

    /**
     * Get all published lessons for a module, ordered by position.
     *
     * @param  array<string>  $with
     */
    public function getByModulePublished(string $moduleId, array $with = []): Collection;
}
