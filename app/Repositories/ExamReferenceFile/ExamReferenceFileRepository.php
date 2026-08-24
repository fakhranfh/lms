<?php

namespace App\Repositories\ExamReferenceFile;

use App\Models\ExamReferenceFile;
use Illuminate\Database\Eloquent\Collection;

class ExamReferenceFileRepository implements ExamReferenceFileRepositoryInterface
{
    public function find(string $id): ?ExamReferenceFile
    {
        return ExamReferenceFile::find($id);
    }

    public function create(array $data): ExamReferenceFile
    {
        return ExamReferenceFile::create($data);
    }

    public function delete(string $id): int
    {
        return ExamReferenceFile::destroy($id);
    }

    public function forAssessmentAndUser(string $assessmentId, string $userId): Collection
    {
        return ExamReferenceFile::where('assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get();
    }
}
