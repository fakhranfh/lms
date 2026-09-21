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
}
