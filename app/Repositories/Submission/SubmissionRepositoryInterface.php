<?php

namespace App\Repositories\Submission;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SubmissionRepositoryInterface
{
    /**
     * Get submissions with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Find a submission by ID with optional relations.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Submission;

    /**
     * Get all submissions for a specific assignment.
     *
     * @param  array<string>  $with
     */
    public function getByAssignment(string $assignmentId, array $with = []): Collection;

    /**
     * Get all submissions made by a specific student.
     *
     * @param  array<string>  $with
     */
    public function getByStudent(string $userId, array $with = []): Collection;

    /**
     * Check whether a student already has a graded submission for an assignment.
     */
    public function hasGradedSubmission(string $assignmentId, string $userId): bool;

    /**
     * Create a new submission.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Submission;

    /**
     * Update a submission.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Submission;

    /**
     * Delete a submission.
     */
    public function delete(string $id): int;

    /**
     * Record an instructor's grade override on a submission.
     */
    public function overrideScore(string $id, float $score, string $feedback, User $instructor): Submission;
}
