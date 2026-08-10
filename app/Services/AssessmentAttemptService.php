<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Repositories\AssessmentAttempt\AssessmentAttemptRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentAttemptService
{
    public function __construct(
        private AssessmentAttemptRepositoryInterface $assessmentAttemptRepository
    ) {}

    /**
     * @return Collection<int, AssessmentAttempt>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentAttemptRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AssessmentAttempt
    {
        return $this->assessmentAttemptRepository->find($id, $with);
    }

    public function create(array $data): AssessmentAttempt
    {
        return $this->assessmentAttemptRepository->create($data);
    }

    public function update(string $id, array $data): AssessmentAttempt
    {
        return $this->assessmentAttemptRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentAttemptRepository->delete($id);
    }

    /**
     * @return Collection<int, AssessmentAttempt>
     */
    public function forAssessmentAndUser(string $assessmentId, string $userId): Collection
    {
        return $this->assessmentAttemptRepository->forAssessmentAndUser($assessmentId, $userId);
    }

    /**
     * @return Collection<int, AssessmentAttempt>
     */
    public function forAssessmentAndGroup(string $assessmentId, string $groupId): Collection
    {
        return $this->assessmentAttemptRepository->forAssessmentAndGroup($assessmentId, $groupId);
    }
}
