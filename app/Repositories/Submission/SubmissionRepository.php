<?php

namespace App\Repositories\Submission;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SubmissionRepository implements SubmissionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Submission::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Submission
    {
        return Submission::with($with)->find($id);
    }

    /**
     * @param  array<string>  $with
     */
    public function getByAssignment(string $assignmentId, array $with = []): Collection
    {
        return Submission::where('assignment_id', $assignmentId)
            ->with($with)
            ->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function getByStudent(string $userId, array $with = []): Collection
    {
        return Submission::where('user_id', $userId)
            ->with($with)
            ->get();
    }

    public function hasGradedSubmission(string $assignmentId, string $userId): bool
    {
        return Submission::where('assignment_id', $assignmentId)
            ->where('user_id', $userId)
            ->where('status', SubmissionStatus::Graded)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Submission
    {
        return Submission::create([
            'assignment_id' => $data['assignment_id'],
            'user_id' => $data['user_id'],
            'student_answer' => $data['student_answer'],
            'status' => $data['status'] ?? SubmissionStatus::Pending,
            'submitted_at' => $data['submitted_at'] ?? now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Submission
    {
        $submission = Submission::findOrFail($id);

        $submission->update([
            'status' => $data['status'] ?? $submission->status,
            'ai_score' => $data['ai_score'] ?? $submission->ai_score,
            'ai_feedback' => $data['ai_feedback'] ?? $submission->ai_feedback,
            'graded_at' => $data['graded_at'] ?? $submission->graded_at,
            'retry_count' => $data['retry_count'] ?? $submission->retry_count,
            'error_message' => $data['error_message'] ?? $submission->error_message,
        ]);

        return $submission;
    }

    public function delete(string $id): int
    {
        return Submission::destroy($id);
    }

    public function overrideScore(string $id, float $score, string $feedback, User $instructor): Submission
    {
        $submission = Submission::findOrFail($id);
        $submission->overrideScore($score, $feedback, $instructor);

        return $submission;
    }
}
