<?php

namespace App\Repositories\LessonMaterial;

use App\Enums\MaterialType;
use App\Models\LessonMaterial;
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
}
