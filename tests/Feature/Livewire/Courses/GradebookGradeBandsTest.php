<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\GradebookGradeBands;
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

class GradebookGradeBandsTest extends TestCase
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

    public function test_user_without_manage_permission_cannot_view(): void
    {
        $this->teacher->givePermissionTo('gradebook.view');
        $this->actingAs($this->teacher);

        Livewire::test(GradebookGradeBands::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_teacher_sees_current_grade_thresholds(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookGradeBands::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('90')
            ->assertSee('80')
            ->assertSee('70')
            ->assertSee('60');
    }

    public function test_teacher_can_save_new_grade_thresholds_and_scores_recalculate(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 100]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 85, 'graded_at' => now()]);

        Livewire::test(GradebookGradeBands::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', ['a' => 95, 'b' => 85, 'c' => 75, 'd' => 65])
            ->assertSet('successMessage', 'Grade ranges updated and letter grades recalculated for every student.');

        $this->course->refresh();
        $this->assertEquals(95, $this->course->grade_band_a_min);
        $this->assertEquals(85, $this->course->grade_band_b_min);
        $this->assertEquals(75, $this->course->grade_band_c_min);
        $this->assertEquals(65, $this->course->grade_band_d_min);
    }

    public function test_thresholds_must_strictly_decrease_from_a_to_d(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookGradeBands::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', ['a' => 70, 'b' => 80, 'c' => 60, 'd' => 50])
            ->assertSet('errorMessage', 'Grade thresholds must strictly decrease from A to D.');

        $this->assertEquals(90, $this->course->fresh()->grade_band_a_min);
    }

    public function test_threshold_out_of_range_is_rejected(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookGradeBands::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', ['a' => 110, 'b' => 80, 'c' => 70, 'd' => -5])
            ->assertSet('errorMessage', 'Every grade threshold must be between 0 and 100.');

        $this->assertEquals(90, $this->course->fresh()->grade_band_a_min);
    }
}
