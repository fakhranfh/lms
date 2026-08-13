<?php

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('creating a course via CourseService auto-provisions a single Attendance assessment', function () {
    $school = School::factory()->create();
    $user = User::factory()->create();

    $course = app(CourseService::class)->create([
        'school_id' => $school->id,
        'title' => 'Physics 101',
        'description' => 'Intro physics',
        'created_by' => $user->id,
        'is_published' => false,
        'slug' => 'physics-101',
    ]);

    $attendanceAssessments = Assessment::where('course_id', $course->id)->where('type', AssessmentType::Attendance)->get();

    expect($attendanceAssessments)->toHaveCount(1);

    $assessment = $attendanceAssessments->first();
    expect($assessment->title)->toBe('Attendance')
        ->and((float) $assessment->weight)->toBe(AssessmentType::Attendance->defaultWeight())
        ->and($assessment->assigned_to->value)->toBe('individual')
        ->and($assessment->start_date)->toBeNull()
        ->and($assessment->end_date)->toBeNull()
        ->and($assessment->status->value)->toBe('published');
});

test('creating a course via CourseService auto-provisions a single Forum Discussion assessment', function () {
    $school = School::factory()->create();
    $user = User::factory()->create();

    $course = app(CourseService::class)->create([
        'school_id' => $school->id,
        'title' => 'Physics 102',
        'description' => 'Intro physics 2',
        'created_by' => $user->id,
        'is_published' => false,
        'slug' => 'physics-102',
    ]);

    $forumDiscussionAssessments = Assessment::where('course_id', $course->id)->where('type', AssessmentType::ForumDiscussion)->get();

    expect($forumDiscussionAssessments)->toHaveCount(1);

    $assessment = $forumDiscussionAssessments->first();
    expect($assessment->title)->toBe('Forum Discussion')
        ->and((float) $assessment->weight)->toBe(AssessmentType::ForumDiscussion->defaultWeight())
        ->and($assessment->assigned_to->value)->toBe('individual')
        ->and($assessment->start_date)->toBeNull()
        ->and($assessment->end_date)->toBeNull()
        ->and($assessment->status->value)->toBe('published');
});

test('ensureAttendanceAssessment is idempotent', function () {
    $course = Course::factory()->create();

    $service = app(CourseService::class);
    $service->ensureAttendanceAssessment($course);
    $service->ensureAttendanceAssessment($course);

    expect(Assessment::where('course_id', $course->id)->where('type', AssessmentType::Attendance)->count())->toBe(1);
});

test('ensureForumDiscussionAssessment is idempotent', function () {
    $course = Course::factory()->create();

    $service = app(CourseService::class);
    $service->ensureForumDiscussionAssessment($course);
    $service->ensureForumDiscussionAssessment($course);

    expect(Assessment::where('course_id', $course->id)->where('type', AssessmentType::ForumDiscussion)->count())->toBe(1);
});

test('backfill migration provisions an Attendance assessment for existing courses missing one', function () {
    $courseWithout = Course::factory()->create();
    $courseWith = Course::factory()->create();
    Assessment::factory()->for($courseWith)->create(['type' => AssessmentType::Attendance]);

    Assessment::where('course_id', $courseWithout->id)->where('type', AssessmentType::Attendance)->delete();

    DB::table('migrations')->where('migration', '2026_08_13_000003_backfill_course_attendance_assessments')->delete();

    Artisan::call('migrate', [
        '--path' => 'database/migrations/2026_08_13_000003_backfill_course_attendance_assessments.php',
        '--realpath' => false,
        '--force' => true,
    ]);

    expect(Assessment::where('course_id', $courseWithout->id)->where('type', AssessmentType::Attendance)->count())->toBe(1)
        ->and(Assessment::where('course_id', $courseWith->id)->where('type', AssessmentType::Attendance)->count())->toBe(1);

    DB::table('migrations')->where('migration', '2026_08_13_000003_backfill_course_attendance_assessments')->delete();
});

test('backfill migration provisions a Forum Discussion assessment for existing courses missing one', function () {
    $courseWithout = Course::factory()->create();
    $courseWith = Course::factory()->create();
    Assessment::factory()->for($courseWith)->create(['type' => AssessmentType::ForumDiscussion]);

    Assessment::where('course_id', $courseWithout->id)->where('type', AssessmentType::ForumDiscussion)->delete();

    DB::table('migrations')->where('migration', '2026_08_13_000004_backfill_course_forum_discussion_assessments')->delete();

    Artisan::call('migrate', [
        '--path' => 'database/migrations/2026_08_13_000004_backfill_course_forum_discussion_assessments.php',
        '--realpath' => false,
        '--force' => true,
    ]);

    expect(Assessment::where('course_id', $courseWithout->id)->where('type', AssessmentType::ForumDiscussion)->count())->toBe(1)
        ->and(Assessment::where('course_id', $courseWith->id)->where('type', AssessmentType::ForumDiscussion)->count())->toBe(1);

    DB::table('migrations')->where('migration', '2026_08_13_000004_backfill_course_forum_discussion_assessments')->delete();
});
