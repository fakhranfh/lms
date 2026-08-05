<?php

namespace App\Repositories\Syllabus;

use App\Models\Syllabus;
use Illuminate\Database\Eloquent\Collection;

interface SyllabusRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Syllabus;

    public function create(array $data): Syllabus;

    public function update(string $id, array $data): Syllabus;

    public function delete(string $id): int;

    public function findByCourse(string $courseId, array $with = []): ?Syllabus;

    /**
     * @param  array<string, array<int, string>>  $selectedMaterialIdsBySection
     */
    public function replaceMaterials(string $syllabusId, array $selectedMaterialIdsBySection): void;
}
