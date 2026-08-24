<?php

namespace App\Repositories\ExamReferenceFile;

use App\Models\ExamReferenceFile;
use Illuminate\Database\Eloquent\Collection;

interface ExamReferenceFileRepositoryInterface
{
    public function find(string $id): ?ExamReferenceFile;

    public function create(array $data): ExamReferenceFile;

    public function delete(string $id): int;

    /**
     * @return Collection<int, ExamReferenceFile>
     */
    public function forAssessmentAndUser(string $assessmentId, string $userId): Collection;
}
