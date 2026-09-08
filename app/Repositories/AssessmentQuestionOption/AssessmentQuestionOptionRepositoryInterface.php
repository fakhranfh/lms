<?php

namespace App\Repositories\AssessmentQuestionOption;

use App\Models\AssessmentQuestionOption;
use Illuminate\Database\Eloquent\Collection;

interface AssessmentQuestionOptionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?AssessmentQuestionOption;

    public function create(array $data): AssessmentQuestionOption;

    public function update(string $id, array $data): AssessmentQuestionOption;

    public function delete(string $id): int;
}
