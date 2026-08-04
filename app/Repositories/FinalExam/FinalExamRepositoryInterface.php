<?php

namespace App\Repositories\FinalExam;

use App\Models\FinalExam;
use Illuminate\Database\Eloquent\Collection;

interface FinalExamRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?FinalExam;

    public function create(array $data): FinalExam;

    public function update(string $id, array $data): FinalExam;

    public function delete(string $id): int;

    public function findByAssessment(string $assessmentId, array $with = []): ?FinalExam;
}
