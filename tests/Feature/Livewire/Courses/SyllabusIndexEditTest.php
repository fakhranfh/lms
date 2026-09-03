<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\SyllabusIndex;
use App\Models\Course;
use App\Models\School;
use App\Models\Syllabus;
use App\Models\SyllabusClassPolicy;
use App\Models\SyllabusEvaluationActivity;
use App\Models\SyllabusLearningOutcome;
use App\Models\SyllabusRubricKeyIndicator;
use App\Models\SyllabusRubricProficiencyLevel;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class SyllabusIndexEditTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $this->actingAs($this->teacher);
    }

    private function editComponent()
    {
        return Livewire::test(SyllabusIndex::class, ['course' => $this->course, 'startInEditMode' => true]);
    }

    public function test_user_cannot_enter_edit_mode_without_permission(): void
    {
        $this->teacher->givePermissionTo('syllabus.view');

        $this->editComponent()->assertStatus(403);
    }

    public function test_user_cannot_edit_syllabus_for_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $otherCourse, 'startInEditMode' => true])
            ->assertStatus(403);
    }

    public function test_first_save_creates_root_and_child_rows(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        // Add/remove happen purely client-side now (see resources/js/syllabus-form.js);
        // tests exercise the resulting state directly via ->set(), the same way the
        // JS writes new rows into the component via $wire.set(path, value, false).
        $this->editComponent()
            ->set('courseDescription', 'Intro to testing')
            ->set('classPolicies.0', ['scope' => 'general', 'content' => 'Be on time', 'order' => 1])
            ->set('learningOutcomes.0', ['code' => 'LO1', 'description' => 'Understand basics', 'order' => 1])
            ->set('evaluations.0', ['class_type' => 'LEC', 'activities' => []])
            ->set('evaluations.0.activities.0', ['activity' => 'Quiz 1', 'weight' => 100, 'learning_outcome_indices' => [0], 'order' => 1])
            ->call('save')
            ->assertRedirect(route('syllabus.index', $this->course));

        $syllabus = Syllabus::where('course_id', $this->course->id)->firstOrFail();

        $this->assertSame('Intro to testing', $syllabus->course_description);
        $this->assertDatabaseHas('syllabus_class_policies', ['syllabus_id' => $syllabus->id, 'content' => 'Be on time']);
        $this->assertDatabaseHas('syllabus_learning_outcomes', ['syllabus_id' => $syllabus->id, 'code' => 'LO1']);
        $this->assertDatabaseHas('syllabus_evaluations', ['syllabus_id' => $syllabus->id, 'class_type' => 'LEC']);

        $activity = SyllabusEvaluationActivity::where('activity', 'Quiz 1')->firstOrFail();
        $this->assertSame(1, $activity->learningOutcomes()->count());
    }

    public function test_second_save_fully_replaces_child_rows(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $syllabus = Syllabus::factory()->for($this->course)->create();
        SyllabusClassPolicy::factory()->for($syllabus)->create(['content' => 'Old policy']);
        SyllabusLearningOutcome::factory()->for($syllabus)->create(['code' => 'OLD1']);

        // Removal nulls the slot rather than splicing it out (see
        // resources/js/syllabus-form.js) — save() prunes the null holes.
        $this->editComponent()
            ->assertSet('classPolicies.0.content', 'Old policy')
            ->set('classPolicies.0', null)
            ->set('classPolicies.1', ['scope' => 'general', 'content' => 'New policy', 'order' => 2])
            ->call('save')
            ->assertRedirect(route('syllabus.index', $this->course));

        $this->assertDatabaseMissing('syllabus_class_policies', ['content' => 'Old policy']);
        $this->assertDatabaseHas('syllabus_class_policies', ['syllabus_id' => $syllabus->id, 'content' => 'New policy']);
        $this->assertDatabaseCount('syllabus_class_policies', 1);
    }

    public function test_learning_outcome_code_uniqueness_is_rejected(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $this->editComponent()
            ->set('learningOutcomes.0', ['code' => 'LO1', 'description' => 'First', 'order' => 1])
            ->set('learningOutcomes.1', ['code' => 'LO1', 'description' => 'Second', 'order' => 2])
            ->call('save')
            ->assertHasErrors(['learningOutcomes']);
    }

    public function test_evaluation_weight_must_total_100(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $this->editComponent()
            ->set('evaluations.0', ['class_type' => 'LEC', 'activities' => []])
            ->set('evaluations.0.activities.0', ['activity' => 'Quiz 1', 'weight' => 90, 'learning_outcome_indices' => [], 'order' => 1])
            ->call('save')
            ->assertHasErrors(['evaluations.0.activities']);
    }

    public function test_evaluation_weight_accepts_float_rounding_to_100(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $this->editComponent()
            ->set('evaluations.0', ['class_type' => 'LEC', 'activities' => []])
            ->set('evaluations.0.activities.0', ['activity' => 'A', 'weight' => 33.33, 'learning_outcome_indices' => [], 'order' => 1])
            ->set('evaluations.0.activities.1', ['activity' => 'B', 'weight' => 33.33, 'learning_outcome_indices' => [], 'order' => 2])
            ->set('evaluations.0.activities.2', ['activity' => 'C', 'weight' => 33.34, 'learning_outcome_indices' => [], 'order' => 3])
            ->call('save')
            ->assertHasNoErrors(['evaluations.0.activities']);
    }

    public function test_rubric_matrix_save_persists_correct_pairs(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $this->editComponent()
            ->set('learningOutcomes.0', ['code' => 'LO1', 'description' => 'Outcome one', 'order' => 1])
            ->set('rubricProficiencyLevels.0', ['label' => 'Excellent', 'score_min' => 80, 'score_max' => 100, 'order' => 1])
            ->set('rubricKeyIndicators.0', ['learning_outcome_index' => 0, 'code' => '1.1', 'description' => 'Demonstrates mastery', 'order' => 1])
            ->set('rubricCells.0.0', 'Achieves excellent mastery')
            ->call('save')
            ->assertRedirect(route('syllabus.index', $this->course));

        $keyIndicator = SyllabusRubricKeyIndicator::where('code', '1.1')->firstOrFail();
        $proficiencyLevel = SyllabusRubricProficiencyLevel::where('label', 'Excellent')->firstOrFail();

        $this->assertDatabaseHas('syllabus_rubric_cells', [
            'rubric_key_indicator_id' => $keyIndicator->id,
            'rubric_proficiency_level_id' => $proficiencyLevel->id,
            'description' => 'Achieves excellent mastery',
        ]);
    }

    public function test_evaluation_activity_learning_outcome_pivot_matches_submitted_checkboxes(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $this->editComponent()
            ->set('learningOutcomes.0', ['code' => 'LO1', 'description' => 'First outcome', 'order' => 1])
            ->set('learningOutcomes.1', ['code' => 'LO2', 'description' => 'Second outcome', 'order' => 2])
            ->set('evaluations.0', ['class_type' => 'LEC', 'activities' => []])
            ->set('evaluations.0.activities.0', ['activity' => 'Quiz', 'weight' => 100, 'learning_outcome_indices' => [1], 'order' => 1])
            ->call('save');

        $activity = SyllabusEvaluationActivity::where('activity', 'Quiz')->firstOrFail();
        $lo2 = SyllabusLearningOutcome::where('code', 'LO2')->firstOrFail();
        $lo1 = SyllabusLearningOutcome::where('code', 'LO1')->firstOrFail();

        $this->assertTrue($activity->learningOutcomes()->where('syllabus_learning_outcomes.id', $lo2->id)->exists());
        $this->assertFalse($activity->learningOutcomes()->where('syllabus_learning_outcomes.id', $lo1->id)->exists());
    }

    public function test_removing_a_row_and_saving_actually_deletes_it(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $syllabus = Syllabus::factory()->for($this->course)->create();
        SyllabusLearningOutcome::factory()->for($syllabus)->create(['code' => 'LO1']);
        SyllabusLearningOutcome::factory()->for($syllabus)->create(['code' => 'LO2']);

        $this->editComponent()
            ->assertCount('learningOutcomes', 2)
            ->set('learningOutcomes.1', null)
            ->call('save')
            ->assertRedirect(route('syllabus.index', $this->course));

        $this->assertDatabaseMissing('syllabus_learning_outcomes', ['code' => 'LO2']);
        $this->assertDatabaseHas('syllabus_learning_outcomes', ['code' => 'LO1']);
        $this->assertDatabaseCount('syllabus_learning_outcomes', 1);
    }

    public function test_visiting_index_redirects_to_edit_when_no_syllabus_and_can_edit(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertRedirect(route('syllabus.edit', $this->course));

        $this->assertDatabaseCount('syllabuses', 0);
    }

    public function test_visiting_index_does_not_redirect_when_cannot_edit(): void
    {
        $this->teacher->givePermissionTo('syllabus.view');

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertNoRedirect();
    }

    public function test_dev_autofill_fills_fields(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $this->editComponent()
            ->call('devAutofill')
            ->assertSet('courseDescription', fn (string $value) => $value !== '')
            ->assertCount('classPolicies', 3)
            ->assertCount('learningOutcomes', 3)
            ->assertCount('rubricProficiencyLevels', 3)
            ->assertCount('rubricKeyIndicators', 3)
            ->assertCount('evaluations', 1)
            ->call('save')
            ->assertRedirect(route('syllabus.index', $this->course));

        $syllabus = Syllabus::where('course_id', $this->course->id)->firstOrFail();

        $this->assertNotEmpty($syllabus->course_description);
        $this->assertDatabaseCount('syllabus_class_policies', 3);
        $this->assertDatabaseCount('syllabus_learning_outcomes', 3);
        $this->assertDatabaseCount('syllabus_rubric_proficiency_levels', 3);
        $this->assertDatabaseCount('syllabus_rubric_key_indicators', 3);
    }

    public function test_dev_autofill_requires_edit_permission(): void
    {
        $this->teacher->givePermissionTo('syllabus.view');

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('devAutofill')
            ->assertStatus(403);
    }
}
