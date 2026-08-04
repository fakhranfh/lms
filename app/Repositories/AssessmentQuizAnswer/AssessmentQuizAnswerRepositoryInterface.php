<?php

namespace App\Repositories\AssessmentQuizAnswer;

use App\Models\AssessmentQuizAnswer;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentQuizAnswerRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentQuizAnswer;

    public function create(array $data): AssessmentQuizAnswer;

    public function update(string $id, array $data): AssessmentQuizAnswer;

    public function delete(string $id): int;

    public function forAttempt(string $attemptId): Collection;
}
