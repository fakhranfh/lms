<?php

use App\Enums\AssessmentType;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use App\Services\DemoCourseGeneratorService;
use App\Services\R2StorageService;
use Illuminate\Support\Collection;

beforeEach(function () {
    $r2Mock = Mockery::mock(R2StorageService::class);
    $r2Mock->shouldReceive('uploadRawContent')->andReturn('https://example.test/dummy.pdf');
    $this->app->instance(R2StorageService::class, $r2Mock);
});

function generateDemoCourses(int $count): Collection
{
    $school = School::factory()->create();
    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole('Teacher');

    return app(DemoCourseGeneratorService::class)->generate($school->id, $teacher->id, $count);
}

test('it generates the requested number of courses with real, non-lorem-ipsum titles', function () {
    $courses = generateDemoCourses(2);

    expect($courses)->toHaveCount(2);

    $courses->each(function (Course $course) {
        expect($course->title)->not->toContain('Lorem')
            ->and($course->title)->not->toBeEmpty();
    });
});

test('each generated course has sessions, syllabus, materials, every assessment type, students, and groups', function () {
    $course = generateDemoCourses(1)->first()->fresh([
        'sessions.materials', 'sessions.subtopics', 'syllabus', 'people', 'groups', 'assessments',
    ]);

    expect($course->sessions)->toHaveCount(6);

    $course->sessions->each(function ($session) {
        expect($session->subtopics)->not->toBeEmpty()
            ->and($session->materials)->toHaveCount(1);
    });

    expect($course->syllabus)->not->toBeNull()
        ->and($course->syllabus->course_description)->toContain($course->title);

    $assessmentTypes = $course->assessments->pluck('type')->map(fn ($type) => $type->value)->unique()->sort()->values()->all();

    expect($assessmentTypes)->toEqual(collect([
        AssessmentType::Attendance,
        AssessmentType::ForumDiscussion,
        AssessmentType::TheoryPersonalAssignment,
        AssessmentType::TheoryTeamAssignment,
        AssessmentType::TheoryQuiz,
        AssessmentType::TheoryFinalExam,
    ])->map(fn ($type) => $type->value)->sort()->values()->all());

    expect($course->people()->count())->toBeGreaterThanOrEqual(9);
    expect($course->groups)->not->toBeEmpty();
});

test('it cycles through the title pool without repeating an existing course title in the same school', function () {
    $school = School::factory()->create();
    $teacher = User::factory()->forSchool($school)->create();
    $teacher->assignRole('Teacher');

    $generator = app(DemoCourseGeneratorService::class);
    $generator->generate($school->id, $teacher->id, 3);
    $generator->generate($school->id, $teacher->id, 2);

    $titles = Course::where('school_id', $school->id)->pluck('title');

    expect($titles->unique())->toHaveCount(5);
});
