<?php

namespace App\Services;

use App\Repositories\School\SchoolRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SchoolService
{
    public function __construct(protected SchoolRepositoryInterface $schoolRepository) {}

    /**
     * Get paginated schools with filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->schoolRepository->paginate($filters, $with, $perPage);
    }

    /**
     * Get all schools.
     */
    public function getAll(): Collection
    {
        return $this->schoolRepository->getAll();
    }

    /**
     * Find a school by ID.
     */
    public function find(string $id)
    {
        return $this->schoolRepository->find($id);
    }

    /**
     * Find a school by ID with eager-loaded relationships.
     *
     * @param  array<string>  $with
     */
    public function findWith(string $id, array $with = [])
    {
        return $this->schoolRepository->findWith($id, $with);
    }
}
