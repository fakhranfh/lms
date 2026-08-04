<?php

namespace App\Repositories\AssessmentAttempt;

use App\Models\AssessmentAttempt;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentAttemptRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentAttempt;

    public function create(array $data): AssessmentAttempt;

    public function update(string $id, array $data): AssessmentAttempt;

    public function delete(string $id): int;

    public function forAssessmentAndUser(string $assessmentId, string $userId): Collection;

    public function forAssessmentAndGroup(string $assessmentId, string $groupId): Collection;
}
