<?php

namespace App\Repositories\AssessmentAnswer;

use App\Models\AssessmentAnswer;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentAnswerRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentAnswer;

    public function create(array $data): AssessmentAnswer;

    public function update(string $id, array $data): AssessmentAnswer;

    public function delete(string $id): int;

    public function findByAttempt(string $attemptId): ?AssessmentAnswer;
}
