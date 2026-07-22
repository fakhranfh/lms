<?php

use App\Models\Assignment;
use App\Models\Lesson;
use App\Repositories\Assignment\AssignmentRepositoryInterface;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->repository = app(AssignmentRepositoryInterface::class);
});

test('can get all assignments for a lesson', function () {
    $lesson = Lesson::factory()->create();

    Assignment::factory()->for($lesson)->count(2)->create();
    Assignment::factory()->create();

    $assignments = $this->repository->getByLesson($lesson->id);

    expect($assignments)->toHaveCount(2);
});

test('can find an assignment by id', function () {
    $assignment = Assignment::factory()->create();

    $found = $this->repository->find($assignment->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($assignment->id);
});

test('returns null when finding non-existent assignment', function () {
    $found = $this->repository->find(Str::uuid()->toString());

    expect($found)->toBeNull();
});

test('can create a new assignment', function () {
    $lesson = Lesson::factory()->create();

    $assignment = $this->repository->create([
        'lesson_id' => $lesson->id,
        'title' => 'Essay Assignment',
        'prompt_question' => 'Explain the causes of X.',
        'max_score' => 100,
        'passing_score' => 70,
    ]);

    expect($assignment->lesson_id)->toBe($lesson->id);
    expect($assignment->title)->toBe('Essay Assignment');
    expect((float) $assignment->max_score)->toBe(100.0);
});

test('can update an assignment', function () {
    $assignment = Assignment::factory()->create(['title' => 'Old Title']);

    $updated = $this->repository->update($assignment->id, ['title' => 'New Title']);

    expect($updated->title)->toBe('New Title');
});

test('can delete an assignment', function () {
    $assignment = Assignment::factory()->create();

    $this->repository->delete($assignment->id);

    expect(Assignment::find($assignment->id))->toBeNull();
});
