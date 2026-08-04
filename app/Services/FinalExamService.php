<?php

namespace App\Services;

use App\Models\FinalExam;
use App\Repositories\FinalExam\FinalExamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FinalExamService
{
    public function __construct(
        private FinalExamRepositoryInterface $finalExamRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->finalExamRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?FinalExam
    {
        return $this->finalExamRepository->find($id, $with);
    }

    public function create(array $data): FinalExam
    {
        return $this->finalExamRepository->create($data);
    }

    public function update(string $id, array $data): FinalExam
    {
        return $this->finalExamRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->finalExamRepository->delete($id);
    }

    public function findByAssessment(string $assessmentId, array $with = []): ?FinalExam
    {
        return $this->finalExamRepository->findByAssessment($assessmentId, $with);
    }
}
