<?php

namespace App\Repositories\AssessmentQuestionScore;

use App\Models\AssessmentQuestionScore;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentQuestionScoreRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentQuestionScore;

    public function create(array $data): AssessmentQuestionScore;

    public function update(string $id, array $data): AssessmentQuestionScore;

    public function delete(string $id): int;

    public function findByAttempt(string $attemptId, array $with = []): Collection;

    public function updateOrCreate(array $attributes, array $values): AssessmentQuestionScore;
}
