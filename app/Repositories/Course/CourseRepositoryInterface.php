<?php

namespace App\Repositories\Course;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface
{
    /**
     * Get courses with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Get a paginated list of courses with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Find a course by ID with optional relations.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Course;

    /**
     * Create a new course.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course;

    /**
     * Update a course.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Course;

    /**
     * Delete a course.
     */
    public function delete(string $id): int;

    /**
     * Check if slug exists for a school.
     */
    public function slugExistsForSchool(string $slug, string $schoolId, ?string $excludeId = null): bool;

    /**
     * Find a soft-deleted course with the given slug in the given school, so
     * it can be restored instead of colliding with the unique (school_id,
     * slug) index, which isn't a partial index and still counts trashed rows.
     */
    public function findTrashedBySlugForSchool(string $slug, string $schoolId): ?Course;

    /**
     * Restore a soft-deleted course rather than creating a duplicate, applying
     * the freshly submitted attributes as if created anew.
     *
     * @param  array<string, mixed>  $data
     */
    public function restore(Course $course, array $data): Course;

    /**
     * Publish a course.
     */
    public function publish(string $id): void;

    /**
     * Unpublish a course.
     */
    public function unpublish(string $id): void;
}
