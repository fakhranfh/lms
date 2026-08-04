<?php

namespace App\Repositories\Quiz;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;

interface QuizRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?Quiz;

    public function create(array $data): Quiz;

    public function update(string $id, array $data): Quiz;

    public function delete(string $id): int;

    public function findByAssessment(string $assessmentId, array $with = []): ?Quiz;
}
