<?php

namespace App\Services;

use App\Models\Module;
use App\Repositories\Module\ModuleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ModuleService
{
    public function __construct(
        private ModuleRepositoryInterface $moduleRepository
    ) {}

    /**
     * Get modules with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->moduleRepository->get($filters, $with);
    }

    /**
     * Find a module by ID.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Module
    {
        return $this->moduleRepository->find($id, $with);
    }

    /**
     * Create a new module.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Module
    {
        return $this->moduleRepository->create($data);
    }

    /**
     * Update a module.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Module
    {
        return $this->moduleRepository->update($id, $data);
    }

    /**
     * Delete a module.
     */
    public function delete(string $id): int
    {
        return $this->moduleRepository->delete($id);
    }

    /**
     * Get next order for a course.
     */
    public function getNextOrder(string $courseId): int
    {
        return $this->moduleRepository->getNextOrder($courseId);
    }

    /**
     * Move a module up in the order.
     */
    public function moveUp(string $id): void
    {
        $this->moduleRepository->moveUp($id);
    }

    /**
     * Move a module down in the order.
     */
    public function moveDown(string $id): void
    {
        $this->moduleRepository->moveDown($id);
    }

    /**
     * Publish a module.
     */
    public function publish(string $id): void
    {
        $this->moduleRepository->publish($id);
    }

    /**
     * Unpublish a module.
     */
    public function unpublish(string $id): void
    {
        $this->moduleRepository->unpublish($id);
    }
}
