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

    private User $schoolAdmin;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->schoolAdmin = User::factory()->forSchool($this->school)->create();
        $this->schoolAdmin->assignRole(Role::firstOrCreate(['name' => RoleName::SchoolAdmin->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));
        $this->schoolAdmin->givePermissionTo('raport.view');
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

    public function test_school_admin_can_view_any_student_in_the_school(): void
    {
        $this->actingAs($this->schoolAdmin);

        Livewire::test(RaportShow::class, ['student' => $this->student])
            ->call('loadData')
            ->assertSee($this->course->title);
    }

    public function test_teacher_cannot_view_a_students_raport_even_when_granted_the_permission(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        Livewire::test(RaportShow::class, ['student' => $this->student])
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

    public function test_overall_final_score_averages_across_every_course(): void
    {
        $this->student->givePermissionTo('raport.view');
        $this->actingAs($this->student);

        $otherCourse = Course::factory()->for($this->school)->create();
        CoursePerson::factory()->for($otherCourse)->student()->create(['user_id' => $this->student->id]);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 100]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        $otherAssessment = Assessment::factory()->for($otherCourse)->create(['type' => 'theory_personal_assignment', 'weight' => 100]);
        AssessmentQuestion::factory()->for($otherAssessment)->create(['points' => 100]);
        $otherAttempt = AssessmentAttempt::factory()->for($otherAssessment)->for($this->student)->create();
        AssessmentScore::factory()->for($otherAttempt, 'attempt')->create(['score' => 40, 'graded_at' => now()]);

        Livewire::test(RaportShow::class, ['student' => $this->student])
            ->call('loadData')
            ->assertViewHas('overallScore', 60.0)
            ->assertViewHas('overallGrade', 'D')
            ->assertSee('Overall Final Score');
    }
}
