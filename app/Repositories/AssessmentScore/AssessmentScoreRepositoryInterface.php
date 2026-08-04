<?php

namespace App\Repositories\AssessmentScore;

use App\Models\AssessmentScore;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentScoreRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentScore;

    public function create(array $data): AssessmentScore;

    public function update(string $id, array $data): AssessmentScore;

    public function delete(string $id): int;

    public function findByAttempt(string $attemptId): ?AssessmentScore;
}
