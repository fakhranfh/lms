<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Assignment\AssignmentRepositoryInterface;
use App\Repositories\Submission\SubmissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        private SubmissionRepositoryInterface $submissionRepository,
        private AssignmentRepositoryInterface $assignmentRepository
    ) {}

    /**
     * Get submissions with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->submissionRepository->get($filters, $with);
    }

    /**
     * Find a submission by ID.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Submission
    {
        return $this->submissionRepository->find($id, $with);
    }

    /**
     * Get all submissions for an assignment.
     *
     * @param  array<string>  $with
     */
    public function getByAssignment(string $assignmentId, array $with = []): Collection
    {
        return $this->submissionRepository->getByAssignment($assignmentId, $with);
    }

    /**
     * Get all submissions made by a student.
     *
     * @param  array<string>  $with
     */
    public function getByStudent(string $userId, array $with = []): Collection
    {
        return $this->submissionRepository->getByStudent($userId, $with);
    }

    /**
     * Submit a student's answer for an assignment.
     * Enforces: assignment must be published, and multiple submissions rule.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function submit(array $data): Submission
    {
        $assignment = $this->assignmentRepository->find($data['assignment_id']);

        if (! $assignment || ! $assignment->is_published) {
            throw ValidationException::withMessages([
                'assignment_id' => 'This assignment is not available for submission.',
            ]);
        }

        if (! $assignment->allow_multiple_submissions
            && $this->submissionRepository->hasGradedSubmission($assignment->id, $data['user_id'])) {
            throw ValidationException::withMessages([
                'assignment_id' => 'This assignment does not allow multiple submissions.',
            ]);
        }

        $submission = $this->submissionRepository->create([
            'assignment_id' => $assignment->id,
            'user_id' => $data['user_id'],
            'student_answer' => $data['student_answer'],
            'status' => SubmissionStatus::Pending,
        ]);

        Log::info('SubmissionService: submission received', [
            'submission_id' => $submission->id,
            'assignment_id' => $assignment->id,
            'status' => SubmissionStatus::Pending->value,
        ]);

        return $submission;
    }

    /**
     * Update a submission (typically used by the grading pipeline).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Submission
    {
        return $this->submissionRepository->update($id, $data);
    }

    /**
     * Delete a submission.
     */
    public function delete(string $id): int
    {
        return $this->submissionRepository->delete($id);
    }

    /**
     * Override a submission's score with an teacher's manual grade.
     */
    public function overrideScore(string $id, float $score, string $feedback, User $teacher): Submission
    {
        return $this->submissionRepository->overrideScore($id, $score, $feedback, $teacher);
    }
}
