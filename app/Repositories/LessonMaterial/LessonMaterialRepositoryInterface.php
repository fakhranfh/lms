<?php

namespace App\Repositories\LessonMaterial;

use App\Enums\MaterialType;
use App\Models\LessonMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface LessonMaterialRepositoryInterface
{
    /**
     * Get lesson materials with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Find a lesson material by ID with optional relations.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?LessonMaterial;

    /**
     * Get all materials for a specific lesson, ordered by order column.
     *
     * @param  array<string>  $with
     */
    public function getByLesson(string $lessonId, array $with = []): Collection;

    /**
     * Get materials for a lesson filtered by type.
     *
     * @param  array<string>  $with
     */
    public function getByLessonAndType(string $lessonId, MaterialType $type, array $with = []): Collection;

    /**
     * Create a new lesson material.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LessonMaterial;

    /**
     * Update a lesson material.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): LessonMaterial;

    /**
     * Delete a lesson material.
     */
    public function delete(string $id): int;

    /**
     * Get next order for a lesson.
     */
    public function getNextOrder(string $lessonId): int;

    /**
     * Reorder materials by setting new order values.
     *
     * @param  array<string, int>  $orderMap  ['material_id' => order_number]
     */
    public function reorder(string $lessonId, array $orderMap): void;

    /**
     * Find a lesson material by ID or throw.
     */
    public function findOrFail(string $id): LessonMaterial;

    /**
     * Sum of file_size across all active materials.
     */
    public function sumActiveFileSize(): int;

    /**
     * Active materials belonging to a school (via lesson.module.course).
     */
    public function getActiveForSchool(string $schoolId): Collection;

    /**
     * Highest version number for a given lesson + title.
     */
    public function getMaxVersion(string $lessonId, string $title): int;

    /**
     * All versions for a lesson + title, newest first.
     */
    public function getVersions(string $lessonId, string $title): Collection;

    /**
     * Deactivate all currently-active versions for a lesson + title.
     */
    public function deactivateVersions(string $lessonId, string $title): void;

    /**
     * Find a specific version of a material by lesson + title + version, or throw.
     */
    public function findVersion(string $lessonId, string $title, int $version): LessonMaterial;

    /**
     * Count how many versions exist for a lesson + title.
     */
    public function countVersions(string $lessonId, string $title): int;

    /**
     * Most recent version other than the given one, for a lesson + title.
     */
    public function findMostRecentOtherVersion(string $lessonId, string $title, int $excludingVersion): ?LessonMaterial;

    /**
     * Query builder for active materials matching the given filters, for pagination/listing.
     *
     * @param  array{school_id?: ?string, course_id?: ?string, module_id?: ?string, lesson_id?: ?string, title?: ?string}  $filters
     */
    public function filteredQuery(array $filters, string $sortBy = 'created_at', string $sortDirection = 'desc'): Builder;
}
