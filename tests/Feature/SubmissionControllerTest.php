<?php

use App\Contracts\AiGradingProvider;
use App\Enums\SubmissionStatus;
use App\Jobs\GradeSubmissionJob;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Submission\SubmissionRepositoryInterface;
use Illuminate\Support\Facades\Queue;

function makeAssignmentForSchool(School $school, array $attributes = []): Assignment
{
    $course = Course::factory()->for($school)->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();

    return Assignment::factory()->for($lesson)->create(array_merge([
        'is_published' => true,
    ], $attributes));
}

test('store creates a pending submission quickly and dispatches grading job', function () {
    Queue::fake();

    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $assignment = makeAssignmentForSchool($school);

    $this->actingAs($student);

    $response = $this->postJson('/submissions', [
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'student_answer' => 'My essay answer.',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', SubmissionStatus::Pending->value);

    $submission = Submission::where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();
    expect($submission)->not->toBeNull();

    Queue::assertPushed(GradeSubmissionJob::class, fn ($job) => $job->submissionId === $submission->id);
});

test('override requires submissions.override-grade permission', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $teacher = User::factory()->forSchool($school)->create();
    $assignment = makeAssignmentForSchool($school);
    $submission = Submission::factory()->for($assignment)->for($student)->create();

    $this->actingAs($teacher);

    $response = $this->patchJson("/submissions/{$submission->id}/override", [
        'teacher_score' => 90,
        'teacher_feedback' => 'Good work.',
    ]);

    $response->assertStatus(403);

    $teacher->givePermissionTo('submissions.override-grade');

    $response = $this->patchJson("/submissions/{$submission->id}/override", [
        'teacher_score' => 90,
        'teacher_feedback' => 'Good work.',
    ]);

    $response->assertStatus(200);
    $submission->refresh();
    expect((float) $submission->teacher_score)->toBe(90.0);
});

test('rate limit triggers on the 4th rapid submission request', function () {
    Queue::fake();

    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $assignment = makeAssignmentForSchool($school, ['allow_multiple_submissions' => true]);

    $this->actingAs($student);

    for ($i = 0; $i < 3; $i++) {
        $response = $this->postJson('/submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'student_answer' => "Attempt {$i}",
        ]);
        $response->assertStatus(201);
    }

    $response = $this->postJson('/submissions', [
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'student_answer' => 'Attempt 4',
    ]);

    $response->assertStatus(429);
});

test('store responds quickly since grading is dispatched asynchronously', function () {
    Queue::fake();

    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $assignment = makeAssignmentForSchool($school);

    $this->actingAs($student);

    $start = microtime(true);

    $response = $this->postJson('/submissions', [
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'student_answer' => 'My essay answer.',
    ]);

    $elapsedMs = (microtime(true) - $start) * 1000;

    $response->assertStatus(201);
    expect($elapsedMs)->toBeLessThan(500);
});

test('submission transitions to graded once the dispatched job runs', function () {
    Queue::fake();

    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $assignment = makeAssignmentForSchool($school);

    $this->actingAs($student);

    $response = $this->postJson('/submissions', [
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'student_answer' => 'My essay answer.',
    ]);

    $submission = Submission::where('assignment_id', $assignment->id)->where('user_id', $student->id)->first();

    $mock = Mockery::mock(AiGradingProvider::class);
    $mock->shouldReceive('buildGradingPrompt')->andReturn('prompt');
    $mock->shouldReceive('gradeEssay')->andReturn([
        'success' => true,
        'score' => 88.0,
        'feedback' => [],
    ]);
    app()->instance(AiGradingProvider::class, $mock);

    (new GradeSubmissionJob($submission->id))->handle(
        app(AiGradingProvider::class),
        app(SubmissionRepositoryInterface::class),
    );

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Graded);
    expect((float) $submission->ai_score)->toBe(88.0);
});

test('retry redispatches the grading job for a failed submission', function () {
    Queue::fake();

    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $teacher = User::factory()->forSchool($school)->create();
    $teacher->givePermissionTo('submissions.grade');
    $assignment = makeAssignmentForSchool($school);
    $submission = Submission::factory()->for($assignment)->for($student)->create([
        'status' => SubmissionStatus::Failed,
    ]);

    $this->actingAs($teacher);

    $response = $this->postJson("/submissions/{$submission->id}/retry");

    $response->assertStatus(200);
    $response->assertJsonPath('data.status', SubmissionStatus::Pending->value);

    Queue::assertPushed(GradeSubmissionJob::class, fn ($job) => $job->submissionId === $submission->id);
});
