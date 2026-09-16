<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\GradebookWeights;
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

class GradebookWeightsTest extends TestCase
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

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_teacher_sees_assessment_types_with_current_weight(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('THEORY: Personal Assignment')
            ->assertSee('20');
    }

    public function test_teacher_can_save_all_weight_changes_at_once_and_scores_recalculate(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $personal = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($personal)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($personal)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        $quiz = Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'weight' => 15]);
        $exam = Assessment::factory()->for($this->course)->create(['type' => 'theory_final_exam', 'weight' => 30]);
        $attendance = Assessment::factory()->for($this->course)->create(['type' => 'attendance', 'weight' => 10, 'start_date' => null, 'end_date' => null]);
        $forum = Assessment::factory()->for($this->course)->create(['type' => 'forum_discussion', 'weight' => 25]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', [
                'theory_personal_assignment' => 50,
                'theory_quiz' => 10,
                'theory_final_exam' => 20,
                'attendance' => 10,
                'forum_discussion' => 10,
            ])
            ->assertSet('successMessage', 'Weights updated and scores recalculated for every student.');

        $this->assertEquals(50.0, $personal->fresh()->weight);
        $this->assertEquals(10.0, $quiz->fresh()->weight);
        $this->assertEquals(20.0, $exam->fresh()->weight);
        $this->assertEquals(10.0, $attendance->fresh()->weight);
        $this->assertEquals(10.0, $forum->fresh()->weight);
    }

    public function test_weights_must_add_up_to_100(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'weight' => 80]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', [
                'theory_personal_assignment' => 50,
                'theory_quiz' => 80,
            ])
            ->assertSet('errorMessage', 'Weights must add up to 100%.');

        $this->assertEquals(20.0, $assessment->fresh()->weight);
    }

    public function test_individual_weight_out_of_range_is_rejected(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'weight' => 80]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', [
                'theory_personal_assignment' => -10,
                'theory_quiz' => 110,
            ])
            ->assertSet('errorMessage', 'Every weight must be between 0 and 100.');

        $this->assertEquals(20.0, $assessment->fresh()->weight);
    }
}
