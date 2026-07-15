<?php

namespace App\Repositories\School;

use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SchoolRepositoryInterface
{
    /**
     * Get schools with pagination, filters, and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all schools.
     */
    public function getAll(): Collection;

    /**
     * Find a school by ID.
     */
    public function find(string $id): ?School;

    /**
     * Find a school by ID with eager-loaded relationships.
     *
     * @param  array<string>  $with
     */
    public function findWith(string $id, array $with = []): ?School;

    /**
     * Create a new school.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): School;

    /**
     * Update a school.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): School;

    /**
     * Delete a school.
     */
    public function delete(string $id): int;
}
