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
     * Find a school by its domain.
     */
    public function findByDomain(string $domain): ?School;

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

    /**
     * Attach a user to a school as a School Admin (school_admins pivot).
     */
    public function attachAdmin(School $school, string $userId): void;

    /**
     * Determine whether the given user has a school_admins pivot row for the school.
     */
    public function administers(School $school, string $userId): bool;

    /**
     * All schools with their users_count eager-loaded, for storage reporting.
     *
     * @return Collection<int, School>
     */
    public function getAllWithUserCounts(): Collection;
}
