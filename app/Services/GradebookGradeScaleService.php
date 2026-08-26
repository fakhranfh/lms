<?php

namespace App\Services;

use App\Models\GradebookGradeScale;
use App\Repositories\GradebookGradeScale\GradebookGradeScaleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GradebookGradeScaleService
{
    public function __construct(
        private GradebookGradeScaleRepositoryInterface $gradebookGradeScaleRepository
    ) {}

    /**
     * @return Collection<int, GradebookGradeScale>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->gradebookGradeScaleRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?GradebookGradeScale
    {
        return $this->gradebookGradeScaleRepository->find($id, $with);
    }

    public function create(array $data): GradebookGradeScale
    {
        return $this->gradebookGradeScaleRepository->create($data);
    }

    public function update(string $id, array $data): GradebookGradeScale
    {
        return $this->gradebookGradeScaleRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->gradebookGradeScaleRepository->delete($id);
    }

    /**
     * @return Collection<int, GradebookGradeScale>
     */
    public function forCourseOrDefault(?string $courseId): Collection
    {
        return $this->gradebookGradeScaleRepository->forCourseOrDefault($courseId);
    }
}
