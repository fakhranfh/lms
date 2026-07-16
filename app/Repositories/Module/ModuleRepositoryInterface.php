<?php

namespace App\Repositories\Module;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;

interface ModuleRepositoryInterface
{
    /**
     * Get modules with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Find a module by ID with optional relations.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Module;

    /**
     * Create a new module.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Module;

    /**
     * Update a module.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Module;

    /**
     * Delete a module.
     */
    public function delete(string $id): int;

    /**
     * Get next order for a course.
     */
    public function getNextOrder(string $courseId): int;

    /**
     * Move a module up in the order.
     */
    public function moveUp(string $id): void;

    /**
     * Move a module down in the order.
     */
    public function moveDown(string $id): void;

    /**
     * Publish a module.
     */
    public function publish(string $id): void;

    /**
     * Unpublish a module.
     */
    public function unpublish(string $id): void;
}
