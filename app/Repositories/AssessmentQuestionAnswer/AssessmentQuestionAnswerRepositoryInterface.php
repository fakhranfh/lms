<?php

namespace App\Repositories\AssessmentQuestionAnswer;

use App\Models\AssessmentQuestionAnswer;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentQuestionAnswerRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentQuestionAnswer;

    public function create(array $data): AssessmentQuestionAnswer;

    public function update(string $id, array $data): AssessmentQuestionAnswer;

    public function delete(string $id): int;

    /**
     * @return Collection<int, AssessmentQuestionAnswer>
     */
    public function forAttempt(string $attemptId): Collection;
}
