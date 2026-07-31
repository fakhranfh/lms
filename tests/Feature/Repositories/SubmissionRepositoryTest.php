<?php

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Submission\SubmissionRepositoryInterface;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->repository = app(SubmissionRepositoryInterface::class);
});

test('can get all submissions for an assignment', function () {
    $assignment = Assignment::factory()->create();

    Submission::factory()->for($assignment)->count(2)->create();
    Submission::factory()->create();

    $submissions = $this->repository->getByAssignment($assignment->id);

    expect($submissions)->toHaveCount(2);
});

test('can get all submissions for a student', function () {
    $user = User::factory()->create();

    Submission::factory()->for($user)->count(2)->create();
    Submission::factory()->create();

    $submissions = $this->repository->getByStudent($user->id);

    expect($submissions)->toHaveCount(2);
});

test('can find a submission by id', function () {
    $submission = Submission::factory()->create();

    $found = $this->repository->find($submission->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($submission->id);
});

test('returns null when finding non-existent submission', function () {
    $found = $this->repository->find(Str::uuid()->toString());

    expect($found)->toBeNull();
});

test('detects an existing graded submission', function () {
    $assignment = Assignment::factory()->create();
    $user = User::factory()->create();

    Submission::factory()->for($assignment)->for($user)->create([
        'status' => SubmissionStatus::Graded,
    ]);

    expect($this->repository->hasGradedSubmission($assignment->id, $user->id))->toBeTrue();
});

test('can create a new submission', function () {
    $assignment = Assignment::factory()->create();
    $user = User::factory()->create();

    $submission = $this->repository->create([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
        'student_answer' => 'My answer.',
    ]);

    expect($submission->status)->toBe(SubmissionStatus::Pending);
    expect($submission->assignment_id)->toBe($assignment->id);
});

test('can update a submission', function () {
    $submission = Submission::factory()->create();

    $updated = $this->repository->update($submission->id, [
        'status' => SubmissionStatus::Processing,
    ]);

    expect($updated->status)->toBe(SubmissionStatus::Processing);
});

test('can delete a submission', function () {
    $submission = Submission::factory()->create();

    $this->repository->delete($submission->id);

    expect(Submission::find($submission->id))->toBeNull();
});

test('can override a submission score', function () {
    $submission = Submission::factory()->create(['ai_score' => 55]);
    $teacher = User::factory()->create();

    $updated = $this->repository->overrideScore($submission->id, 90, 'Great work', $teacher);

    expect((float) $updated->teacher_score)->toBe(90.0);
    expect($updated->teacher_feedback)->toBe('Great work');
    expect($updated->reviewed_by)->toBe($teacher->id);
});
