<?php

namespace Tests\Feature\Livewire\Raport;

use App\Enums\RoleName;
use App\Livewire\Raport\RaportShow;
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

class RaportShowTest extends TestCase
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

    public function test_student_can_view_their_own_raport(): void
    {
        $this->student->givePermissionTo('raport.view');
        $this->actingAs($this->student);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        Livewire::test(RaportShow::class, ['student' => $this->student])
            ->call('loadData')
            ->assertSee($this->course->title)
            ->assertSee('Final Score')
            ->assertSee('THEORY: Personal Assignment');
    }

    public function test_teacher_can_view_a_student_they_teach(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        Livewire::test(RaportShow::class, ['student' => $this->student])
            ->call('loadData')
            ->assertSee($this->course->title);
    }

    public function test_teacher_cannot_view_a_student_they_do_not_teach(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        $otherStudent = User::factory()->forSchool($this->school)->create();

        Livewire::test(RaportShow::class, ['student' => $otherStudent])
            ->assertStatus(403);
    }

    public function test_student_cannot_view_another_students_raport(): void
    {
        $this->student->givePermissionTo('raport.view');
        $this->actingAs($this->student);

        $otherStudent = User::factory()->forSchool($this->school)->create();
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $otherStudent->id]);

        Livewire::test(RaportShow::class, ['student' => $otherStudent])
            ->assertStatus(403);
    }
}
