<?php

namespace Tests\Feature\Livewire\Raport;

use App\Enums\RoleName;
use App\Livewire\Raport\RaportIndex;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class RaportIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        app()->detectEnvironment(fn () => 'local');

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));
        $this->course = Course::factory()->for($this->school)->create();

        CoursePerson::factory()->for($this->course)->teacher()->create(['user_id' => $this->teacher->id]);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_user_without_permission_cannot_view_raport(): void
    {
        $this->actingAs($this->student);

        Livewire::test(RaportIndex::class)->assertStatus(403);
    }

    public function test_student_sees_only_their_own_enrolled_courses(): void
    {
        $this->student->givePermissionTo('raport.view');
        $this->actingAs($this->student);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->assertSee($this->course->title)
            ->assertSee('Final Score')
            ->assertSee('THEORY: Personal Assignment');
    }

    public function test_teacher_sees_every_student_enrolled_in_their_taught_courses(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->total() === 1
                && $studentRows->first()['user']->id === $this->student->id);
    }

    public function test_teacher_does_not_see_students_from_courses_they_do_not_teach(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        $otherCourse = Course::factory()->for($this->school)->create();
        $otherStudent = User::factory()->forSchool($this->school)->create();
        CoursePerson::factory()->for($otherCourse)->student()->create(['user_id' => $otherStudent->id]);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->total() === 1
                && ! $studentRows->contains(fn (array $row) => $row['user']->id === $otherStudent->id));
    }

    public function test_teacher_sees_each_student_once_even_when_enrolled_in_several_of_their_courses(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        $otherCourse = Course::factory()->for($this->school)->create();
        CoursePerson::factory()->for($otherCourse)->teacher()->create(['user_id' => $this->teacher->id]);
        CoursePerson::factory()->for($otherCourse)->student()->create(['user_id' => $this->student->id]);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->total() === 1
                && $studentRows->first()['user']->id === $this->student->id);
    }

    public function test_teacher_student_grid_links_to_the_student_raport_show_page(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->assertSee($this->student->name)
            ->assertSee(route('raport.show', $this->student), false);
    }

    public function test_generate_raport_scores_is_forbidden_outside_local_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->teacher->givePermissionTo(['raport.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->call('generateRaportScores')
            ->assertStatus(403);

        app()->detectEnvironment(fn () => 'testing');
    }

    public function test_generate_raport_scores_enrolls_every_student_into_every_course_in_the_school(): void
    {
        $this->teacher->givePermissionTo(['raport.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $otherCourse = Course::factory()->for($this->school)->create();
        $unenrolledStudent = User::factory()->forSchool($this->school)->create();
        $unenrolledStudent->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->call('generateRaportScores');

        $this->assertTrue(CoursePerson::where('course_id', $this->course->id)->where('user_id', $unenrolledStudent->id)->exists());
        $this->assertTrue(CoursePerson::where('course_id', $otherCourse->id)->where('user_id', $unenrolledStudent->id)->exists());
        $this->assertTrue(CoursePerson::where('course_id', $otherCourse->id)->where('user_id', $this->student->id)->exists());
    }

    public function test_generate_raport_scores_grades_every_configured_assessment(): void
    {
        $this->teacher->givePermissionTo(['raport.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->call('generateRaportScores');

        $this->assertTrue(
            AssessmentAttempt::where('assessment_id', $assessment->id)->where('user_id', $this->student->id)->exists()
        );
    }

    public function test_generate_raport_scores_creates_and_grades_assessment_types_the_course_is_missing(): void
    {
        $this->teacher->givePermissionTo(['raport.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        // The course starts with no Assessments at all (not even the
        // auto-provisioned Attendance/Forum Discussion ones), so every type
        // in the report card should be created and graded, not just left
        // blank as Gradebook's single-course randomizer would leave it.
        $this->assertSame(0, Assessment::where('course_id', $this->course->id)->count());

        Livewire::test(RaportIndex::class)
            ->call('loadData')
            ->call('generateRaportScores');

        $types = Assessment::where('course_id', $this->course->id)->pluck('type')->map(fn ($type) => $type->value)->sort()->values()->all();

        $this->assertSame([
            'attendance',
            'forum_discussion',
            'theory_final_exam',
            'theory_personal_assignment',
            'theory_quiz',
            'theory_team_assignment',
        ], $types);

        $personalAssignment = Assessment::where('course_id', $this->course->id)->where('type', 'theory_personal_assignment')->firstOrFail();

        $this->assertTrue(
            AssessmentAttempt::where('assessment_id', $personalAssignment->id)->where('user_id', $this->student->id)->exists()
        );
    }
}
