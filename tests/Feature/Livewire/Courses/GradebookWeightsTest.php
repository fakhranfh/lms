<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Livewire\Courses\GradebookWeights;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\Session;
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

    public function test_draft_assessments_are_still_manageable_on_the_weights_page(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'title' => 'Published Assignment', 'weight' => 20, 'status' => 'published']);
        Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'title' => 'Draft Assignment', 'weight' => 10, 'status' => 'draft']);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Published Assignment')
            ->assertSee('Draft Assignment')
            ->assertSee('30');
    }

    public function test_teacher_sees_assessment_title_for_a_type_with_a_single_assessment(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'title' => 'Concept Check Quiz', 'weight' => 10]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSeeInOrder(['THEORY: Quiz', 'Concept Check Quiz']);
    }

    public function test_teacher_sees_session_breakdown_for_attendance_and_forum_discussion(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create(['type' => 'attendance', 'weight' => 10, 'start_date' => null, 'end_date' => null]);
        Assessment::factory()->for($this->course)->create(['type' => 'forum_discussion', 'weight' => 10, 'start_date' => null, 'end_date' => null]);

        Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
        Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::Online]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSeeInOrder(['Attendance', 'Virtual Class'])
            ->assertSeeInOrder(['Forum Discussion', 'Online']);
    }

    public function test_teacher_sees_per_assessment_titles_for_a_type_with_multiple_assessments(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'title' => 'Quiz Week 1', 'weight' => 10]);
        Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'title' => 'Quiz Week 2', 'weight' => 10]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Quiz Week 1')
            ->assertSee('Quiz Week 2');
    }

    public function test_type_weight_is_split_evenly_across_multiple_assessments_on_save(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'weight' => 10]);
        $second = Assessment::factory()->for($this->course)->create(['type' => 'theory_quiz', 'weight' => 30]);
        $third = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 60]);

        Livewire::test(GradebookWeights::class, ['course' => $this->course])
            ->call('loadData')
            ->call('save', [
                'theory_quiz' => 40,
                'theory_personal_assignment' => 60,
            ]);

        $this->assertEquals(20.0, $first->fresh()->weight);
        $this->assertEquals(20.0, $second->fresh()->weight);
        $this->assertEquals(60.0, $third->fresh()->weight);
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
