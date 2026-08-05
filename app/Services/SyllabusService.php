<?php

namespace App\Services;

use App\Models\Syllabus;
use App\Repositories\Syllabus\SyllabusRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusService
{
    public function __construct(
        private SyllabusRepositoryInterface $syllabusRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Syllabus
    {
        return $this->syllabusRepository->find($id, $with);
    }

    public function create(array $data): Syllabus
    {
        return $this->syllabusRepository->create($data);
    }

    public function update(string $id, array $data): Syllabus
    {
        return $this->syllabusRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusRepository->delete($id);
    }

    public function findByCourse(string $courseId, array $with = []): ?Syllabus
    {
        return $this->syllabusRepository->findByCourse($courseId, $with);
    }

    /**
     * @param  array<string, array<int, string>>  $selectedMaterialIdsBySection
     */
    public function replaceMaterials(string $syllabusId, array $selectedMaterialIdsBySection): void
    {
        $this->syllabusRepository->replaceMaterials($syllabusId, $selectedMaterialIdsBySection);
    }
}
