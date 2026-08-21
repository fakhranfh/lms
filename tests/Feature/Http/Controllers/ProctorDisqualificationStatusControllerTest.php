<?php

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSessionStatus;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\ProctorSession;
use App\Models\Role;
use App\Models\School;
use App\Models\User;

test('disqualification stream reports the terminated status immediately when the job already finished', function () {
    $school = School::factory()->create();
    $student = User::factory()->forSchool($school)->create();
    $course = Course::factory()->for($school)->create();

    $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $school->id]);
    $student->assignRole($studentRole);
    $student->givePermissionTo('assessment.view');

    CoursePerson::factory()->for($course)->student()->create(['user_id' => $student->id]);

    $assessment = Assessment::factory()->for($course)->create([
        'type' => AssessmentType::TheoryFinalExam,
        'end_date' => now()->addWeek(),
    ]);
    $period = Period::factory()->for($course)->create();
    FinalExam::factory()->for($assessment)->create([
        'period_id' => $period->id,
        'exam_type' => FinalExamType::OpenBook,
    ]);

    $attempt = AssessmentAttempt::factory()->for($assessment)->create([
        'user_id' => $student->id,
        'attempt_number' => 1,
        'started_at' => now(),
        'submitted_at' => now(),
    ]);
    ProctorSession::factory()->for($attempt, 'attempt')->create([
        'status' => ProctorSessionStatus::Terminated,
        'review_decision' => ProctorReviewDecision::Disqualified,
    ]);

    $response = $this->actingAs($student)
        ->get(route('assessments.final-exam.proctor.disqualification-stream', $assessment));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
    expect($response->streamedContent())->toContain('"status":"terminated"');
});
