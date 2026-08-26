<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\GradebookIndex;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class GradebookIndexTest extends TestCase
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
        $this->course = Course::factory()->for($this->school)->create();

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_user_without_permission_cannot_view_gradebook(): void
    {
        $this->actingAs($this->student);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_student_sees_own_final_score_card(): void
    {
        $this->student->givePermissionTo('gradebook.view');
        $this->actingAs($this->student);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Final Score')
            ->assertSee('Theory Personal Assignment');
    }

    public function test_teacher_sees_student_roster_and_can_drill_down(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee($this->student->name)
            ->call('selectStudent', $this->student->id)
            ->assertSet('selectedStudentId', $this->student->id);
    }

    public function test_student_cannot_manage_grading_scale(): void
    {
        $this->student->givePermissionTo('gradebook.view');
        $this->actingAs($this->student);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('saveGradeScale')
            ->assertStatus(403);
    }

    public function test_teacher_can_manage_grading_scale(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set('scaleLabel', 'A')
            ->set('scaleMin', '85')
            ->set('scaleMax', '100')
            ->call('saveGradeScale')
            ->assertSet('successMessage', 'Grading scale saved.');

        $this->assertDatabaseHas('gradebook_grade_scales', [
            'course_id' => $this->course->id,
            'label' => 'A',
            'score_min' => 85,
            'score_max' => 100,
        ]);
    }
}
