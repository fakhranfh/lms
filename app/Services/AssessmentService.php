<?php

namespace App\Services;

use App\Models\Assessment;
use App\Repositories\Assessment\AssessmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentService
{
    public function __construct(
        private AssessmentRepositoryInterface $assessmentRepository
    ) {}

    /**
     * @return Collection<int, Assessment>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Assessment
    {
        return $this->assessmentRepository->find($id, $with);
    }

    public function create(array $data): Assessment
    {
        return $this->assessmentRepository->create($data);
    }

    public function update(string $id, array $data): Assessment
    {
        return $this->assessmentRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentRepository->delete($id);
    }

    /**
     * @return Collection<int, Assessment>
     */
    public function forCourse(string $courseId): Collection
    {
        return $this->assessmentRepository->forCourse($courseId);
    }
}
