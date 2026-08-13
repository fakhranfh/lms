<?php

use App\Enums\AttendanceStatus;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceScoringService;

test('computeForUser derives percentage and score from sessions in the assessment date range', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'attendance',
        'weight' => 10,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(5),
    ]);

    $inRangeAttended = Session::factory()->create(['course_id' => $course->id, 'date_start' => now()]);
    Attendance::factory()->create(['session_id' => $inRangeAttended->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    $inRangeMissed = Session::factory()->create(['course_id' => $course->id, 'date_start' => now()->addDay()]);
    Attendance::factory()->create(['session_id' => $inRangeMissed->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Absent]);

    // Out of range — should not count.
    Session::factory()->create(['course_id' => $course->id, 'date_start' => now()->addMonths(2)]);

    $service = app(AttendanceScoringService::class);
    $computed = $service->computeForUser($assessment, $user->id);

    expect($computed['total'])->toBe(2)
        ->and($computed['attended'])->toBe(1)
        ->and($computed['percentage'])->toBe(50.0)
        ->and($computed['score'])->toBe(5.0);
});

test('recomputeForUser writes a single attempt and score row that updates on recompute', function () {
    $course = Course::factory()->create();
    $user = User::factory()->create();

    $assessment = Assessment::factory()->for($course)->create([
        'type' => 'attendance',
        'weight' => 10,
        'start_date' => null,
        'end_date' => null,
    ]);

    $session = Session::factory()->create(['course_id' => $course->id]);
    Attendance::factory()->create(['session_id' => $session->id, 'user_id' => $user->id, 'status' => AttendanceStatus::Present]);

    $service = app(AttendanceScoringService::class);
    $attempt = $service->recomputeForUser($assessment, $user->id);

    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 10]);
    $this->assertDatabaseCount('assessment_attempts', 1);

    Attendance::where('session_id', $session->id)->update(['status' => AttendanceStatus::Absent]);
    $service->recomputeForUser($assessment, $user->id);

    $this->assertDatabaseCount('assessment_attempts', 1);
    $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 0]);
});
