<?php

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\GradebookGradeScale;
use App\Models\Session;
use App\Models\User;
use App\Services\GradebookGradeScaleService;
use App\Services\GradebookScoringService;

test('computeForUser weights a graded Personal Assignment percentage by its question points', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
    AssessmentQuestion::factory()->for($assessment)->create(['points' => 50]);
    AssessmentQuestion::factory()->for($assessment)->create(['points' => 50]);

    $attempt = AssessmentAttempt::factory()->for($assessment)->for($user)->create();
    AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 75, 'graded_at' => now()]);

    $result = app(GradebookScoringService::class)->computeForUser($course, $user->id);

    $typeRow = collect($result['types'])->firstWhere('type', AssessmentType::TheoryPersonalAssignment);

    expect($typeRow['weight'])->toBe(20.0)
        ->and($typeRow['score'])->toBe(75.0)
        ->and($result['final']['score'])->toBe(15.0);
});

test('ungraded assessments of a type are excluded from the weighted average, not treated as zero', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $gradedAssessment = Assessment::factory()->for($course)->create(['type' => 'theory_personal_assignment', 'weight' => 10]);
    AssessmentQuestion::factory()->for($gradedAssessment)->create(['points' => 100]);
    $attempt = AssessmentAttempt::factory()->for($gradedAssessment)->for($user)->create();
    AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

    $ungradedAssessment = Assessment::factory()->for($course)->create(['type' => 'theory_personal_assignment', 'weight' => 10]);
    AssessmentQuestion::factory()->for($ungradedAssessment)->create(['points' => 100]);

    $result = app(GradebookScoringService::class)->computeForUser($course, $user->id);

    $typeRow = collect($result['types'])->firstWhere('type', AssessmentType::TheoryPersonalAssignment);

    expect($typeRow['weight'])->toBe(20.0)
        ->and($typeRow['score'])->toBe(80.0);
});

test('a type with no graded assessments shows a null score and does not contribute to the final score', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
    AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);

    $result = app(GradebookScoringService::class)->computeForUser($course, $user->id);

    $typeRow = collect($result['types'])->firstWhere('type', AssessmentType::TheoryPersonalAssignment);

    expect($typeRow['score'])->toBeNull()
        ->and($result['final']['score'])->toBeNull();
});

test('Attendance and Forum Discussion types build a per-session breakdown', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'attendance',
        'weight' => 10,
        'start_date' => null,
        'end_date' => null,
    ]);

    $attended = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Attendance::factory()->create(['session_id' => $attended->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    $missed = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Attendance::factory()->create(['session_id' => $missed->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Absent]);

    $result = app(GradebookScoringService::class)->computeForUser($course, $user->id);

    $typeRow = collect($result['types'])->firstWhere('type', AssessmentType::Attendance);

    expect($typeRow['score'])->toBe(50.0)
        ->and($typeRow['sessions'])->toHaveCount(2)
        ->and($typeRow['sessions'][0]['weight'])->toBe(5.0);
});

test('personal/team/quiz/final-exam types have no session breakdown', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
    AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
    $attempt = AssessmentAttempt::factory()->for($assessment)->for($user)->create();
    AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 90, 'graded_at' => now()]);

    $result = app(GradebookScoringService::class)->computeForUser($course, $user->id);

    $typeRow = collect($result['types'])->firstWhere('type', AssessmentType::TheoryPersonalAssignment);

    expect($typeRow['sessions'])->toBe([]);
});

test('final score resolves a grade from the course grading scale', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    GradebookGradeScale::factory()->for($course)->create(['label' => 'A', 'score_min' => 85, 'score_max' => 100, 'order' => 1]);
    GradebookGradeScale::factory()->for($course)->create(['label' => 'B', 'score_min' => 70, 'score_max' => 84, 'order' => 2]);

    $assessment = Assessment::factory()->for($course)->create(['type' => 'theory_personal_assignment', 'weight' => 100]);
    AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
    $attempt = AssessmentAttempt::factory()->for($assessment)->for($user)->create();
    AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 90, 'graded_at' => now()]);

    $result = app(GradebookScoringService::class)->computeForUser($course, $user->id);

    expect($result['final']['score'])->toBe(90.0)
        ->and($result['final']['grade'])->toBe('A');
});

test('recomputeForUser persists a GradebookEntry per assessment type with matching session entries', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'attendance',
        'weight' => 10,
        'start_date' => null,
        'end_date' => null,
    ]);

    $session = Session::factory()->create(['course_id' => $course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    $service = app(GradebookScoringService::class);
    $service->recomputeForUser($course, $user->id);

    $this->assertDatabaseHas('gradebook_entries', [
        'course_id' => $course->id,
        'user_id' => $user->id,
        'assessment_type' => 'attendance',
        'score' => 100,
    ]);
    $this->assertDatabaseCount('gradebook_session_entries', 1);

    Attendance::where('session_id', $session->id)->update(['status' => AttendanceStatus::Absent]);
    $service->recomputeForUser($course, $user->id);

    $this->assertDatabaseCount('gradebook_entries', 1);
    $this->assertDatabaseHas('gradebook_entries', ['course_id' => $course->id, 'user_id' => $user->id, 'score' => 0]);
});

test('forCourseOrDefault falls back to the school-level default scale when the course has none', function () {
    $course = Course::factory()->create();

    GradebookGradeScale::factory()->create(['course_id' => null, 'label' => 'A', 'order' => 1]);

    $scales = app(GradebookGradeScaleService::class)->forCourseOrDefault($course->id);

    expect($scales)->toHaveCount(1)
        ->and($scales->first()->label)->toBe('A');
});
