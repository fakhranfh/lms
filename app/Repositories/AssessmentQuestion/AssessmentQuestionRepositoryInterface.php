<?php

namespace App\Repositories\AssessmentQuestion;

use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentQuestionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentQuestion;

    public function create(array $data): AssessmentQuestion;

    public function update(string $id, array $data): AssessmentQuestion;

    public function delete(string $id): int;
}
