<?php

use App\Enums\AssessmentType;
use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Database\Seeders\AssessmentSeeder;
use Illuminate\Support\Str;

function seedQuizCourse(): Course
{
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $session = Session::factory()->for($course)->create([
        'date_start' => now()->subWeek(),
        'date_end' => now()->addWeek(),
    ]);

    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole('Teacher');
    CoursePerson::create([
        'id' => (string) Str::uuid(),
        'course_id' => $course->id,
        'user_id' => $teacher->id,
        'role_in_course' => RoleInCourse::Teacher,
        'enrolled_at' => now(),
        'status' => CourseMembershipStatus::Active,
    ]);

    (new AssessmentSeeder)->run();

    return $course->fresh(['assessments', 'sessions']);
}

test('assessment seeder creates a quiz linked to the course session', function () {
    $course = seedQuizCourse();

    $quizAssessment = $course->assessments->firstWhere('type', AssessmentType::TheoryQuiz);

    expect($quizAssessment)->not->toBeNull()
        ->and($quizAssessment->session_id)->toBe($course->sessions->first()->id)
        ->and($quizAssessment->quiz)->not->toBeNull()
        ->and($quizAssessment->quiz->start_date->equalTo($course->sessions->first()->date_start))->toBeTrue()
        ->and($quizAssessment->quiz->due_date->equalTo($course->sessions->first()->date_end))->toBeTrue();
});

test('assessment seeder creates only multiple choice quiz questions, each with an answer key', function () {
    $course = seedQuizCourse();

    $quiz = $course->assessments->firstWhere('type', AssessmentType::TheoryQuiz)->quiz;
    $types = $quiz->questions->pluck('question_type')->map(fn ($type) => $type->value)->unique()->all();

    expect($quiz->questions)->toHaveCount(4)
        ->and($quiz->total_question)->toBe(4)
        ->and($types)->toBe(['multiple_choice']);

    $quiz->questions->each(function ($question) {
        expect($question->options->count())->toBeGreaterThanOrEqual(2)
            ->and($question->options->where('is_correct', true)->count())->toBe(1);
    });
});

test('assessment seeder seeds quizzes with unlimited attempts', function () {
    $course = seedQuizCourse();

    $quiz = $course->assessments->firstWhere('type', AssessmentType::TheoryQuiz)->quiz;

    expect($quiz->total_attempts)->toBeNull();
});

test('assessment seeder does not create a quiz when the course has no sessions', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();

    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole('Teacher');
    CoursePerson::create([
        'id' => (string) Str::uuid(),
        'course_id' => $course->id,
        'user_id' => $teacher->id,
        'role_in_course' => RoleInCourse::Teacher,
        'enrolled_at' => now(),
        'status' => CourseMembershipStatus::Active,
    ]);

    (new AssessmentSeeder)->run();

    expect(Assessment::where('course_id', $course->id)->where('type', AssessmentType::TheoryQuiz)->exists())->toBeFalse();
});

test('assessment seeder picks a session that has not ended yet when one is available', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $pastSession = Session::factory()->for($course)->create([
        'date_start' => now()->subWeeks(2),
        'date_end' => now()->subWeek(),
    ]);
    $upcomingSession = Session::factory()->for($course)->create([
        'date_start' => now()->addDay(),
        'date_end' => now()->addWeek(),
    ]);

    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole('Teacher');
    CoursePerson::create([
        'id' => (string) Str::uuid(),
        'course_id' => $course->id,
        'user_id' => $teacher->id,
        'role_in_course' => RoleInCourse::Teacher,
        'enrolled_at' => now(),
        'status' => CourseMembershipStatus::Active,
    ]);

    (new AssessmentSeeder)->run();

    $quizAssessment = Assessment::where('course_id', $course->id)->where('type', AssessmentType::TheoryQuiz)->first();

    expect($quizAssessment->session_id)->toBe($upcomingSession->id)
        ->and($quizAssessment->end_date->isFuture())->toBeTrue()
        ->and($pastSession->id)->not->toBe($upcomingSession->id);
});

test('assessment seeder relinks an already-expired quiz to a still-open session', function () {
    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $expiredSession = Session::factory()->for($course)->create([
        'date_start' => now()->subWeeks(3),
        'date_end' => now()->subWeeks(2),
    ]);

    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole('Teacher');
    CoursePerson::create([
        'id' => (string) Str::uuid(),
        'course_id' => $course->id,
        'user_id' => $teacher->id,
        'role_in_course' => RoleInCourse::Teacher,
        'enrolled_at' => now(),
        'status' => CourseMembershipStatus::Active,
    ]);

    (new AssessmentSeeder)->run();
    $quizAssessment = Assessment::where('course_id', $course->id)->where('type', AssessmentType::TheoryQuiz)->first();

    // Simulate time passing: the quiz's only session expires after it was seeded.
    $expiredSession->update(['date_end' => now()->subDay()]);
    $quizAssessment->update(['end_date' => now()->subDay()]);
    $quizAssessment->quiz->update(['due_date' => now()->subDay()]);

    $newSession = Session::factory()->for($course)->create([
        'date_start' => now()->addDays(2),
        'date_end' => now()->addWeeks(2),
    ]);

    (new AssessmentSeeder)->run();

    $refreshed = $quizAssessment->fresh('quiz');

    expect($refreshed->session_id)->toBe($newSession->id)
        ->and($refreshed->end_date->isFuture())->toBeTrue()
        ->and($refreshed->quiz->due_date->isFuture())->toBeTrue();
});
