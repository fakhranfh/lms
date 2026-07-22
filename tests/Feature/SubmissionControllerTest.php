<?php

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\Submission;
use App\Models\User;

function makeAssignmentForSchool(School $school, array $attributes = []): Assignment
{
    $course = Course::factory()->for($school)->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();

    return Assignment::factory()->for($lesson)->create(array_merge([
        'is_published' => true,
    ], $attributes));
}

test('store creates a pending submission quickly', function () {
    $school = School::factory()->create();
    $student = User::factory()->for($school)->create();
    $assignment = makeAssignmentForSchool($school);

    $this->actingAs($student);

    $response = $this->postJson('/submissions', [
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'student_answer' => 'My essay answer.',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.status', SubmissionStatus::Pending->value);

    expect(Submission::where('assignment_id', $assignment->id)->where('user_id', $student->id)->exists())->toBeTrue();
});

test('override requires submissions.override-grade permission', function () {
    $school = School::factory()->create();
    $student = User::factory()->for($school)->create();
    $instructor = User::factory()->for($school)->create();
    $assignment = makeAssignmentForSchool($school);
    $submission = Submission::factory()->for($assignment)->for($student)->create();

    $this->actingAs($instructor);

    $response = $this->patchJson("/submissions/{$submission->id}/override", [
        'instructor_score' => 90,
        'instructor_feedback' => 'Good work.',
    ]);

    $response->assertStatus(403);

    $instructor->givePermissionTo('submissions.override-grade');

    $response = $this->patchJson("/submissions/{$submission->id}/override", [
        'instructor_score' => 90,
        'instructor_feedback' => 'Good work.',
    ]);

    $response->assertStatus(200);
    $submission->refresh();
    expect((float) $submission->instructor_score)->toBe(90.0);
});

test('rate limit triggers on the 4th rapid submission request', function () {
    $school = School::factory()->create();
    $student = User::factory()->for($school)->create();
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
