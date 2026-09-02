<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\SyllabusIndex;
use App\Models\Course;
use App\Models\MediaLibraryItem;
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

    public function test_user_cannot_enter_edit_mode_without_permission(): void
    {
        $this->teacher->givePermissionTo('syllabus.view');

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->assertStatus(403);
    }

    public function test_user_cannot_edit_syllabus_for_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }

    public function test_first_save_creates_root_and_child_rows(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->set('courseDescription', 'Intro to testing')
            ->call('addClassPolicy')
            ->set('classPolicies.0.scope', 'general')
            ->set('classPolicies.0.content', 'Be on time')
            ->call('addLearningOutcome')
            ->set('learningOutcomes.0.code', 'LO1')
            ->set('learningOutcomes.0.description', 'Understand basics')
            ->call('addEvaluationGroup')
            ->set('evaluations.0.class_type', 'LEC')
            ->call('addEvaluationActivity', 0)
            ->set('evaluations.0.activities.0.activity', 'Quiz 1')
            ->set('evaluations.0.activities.0.weight', 100)
            ->set('evaluations.0.activities.0.learning_outcome_indices', [0])
            ->call('save')
            ->assertSet('editing', false);

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
        $lo = SyllabusLearningOutcome::factory()->for($syllabus)->create(['code' => 'OLD1']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->assertSet('classPolicies.0.content', 'Old policy')
            ->call('removeClassPolicy', 0)
            ->call('addClassPolicy')
            ->set('classPolicies.0.scope', 'general')
            ->set('classPolicies.0.content', 'New policy')
            ->call('save')
            ->assertSet('editing', false);

        $this->assertDatabaseMissing('syllabus_class_policies', ['content' => 'Old policy']);
        $this->assertDatabaseHas('syllabus_class_policies', ['syllabus_id' => $syllabus->id, 'content' => 'New policy']);
        $this->assertDatabaseCount('syllabus_class_policies', 1);
    }

    public function test_learning_outcome_code_uniqueness_is_rejected(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->call('addLearningOutcome')
            ->set('learningOutcomes.0.code', 'LO1')
            ->set('learningOutcomes.0.description', 'First')
            ->call('addLearningOutcome')
            ->set('learningOutcomes.1.code', 'LO1')
            ->set('learningOutcomes.1.description', 'Second')
            ->call('save')
            ->assertHasErrors(['learningOutcomes']);
    }

    public function test_evaluation_weight_must_total_100(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->call('addEvaluationGroup')
            ->set('evaluations.0.class_type', 'LEC')
            ->call('addEvaluationActivity', 0)
            ->set('evaluations.0.activities.0.activity', 'Quiz 1')
            ->set('evaluations.0.activities.0.weight', 90)
            ->call('save')
            ->assertHasErrors(['evaluations.0.activities']);
    }

    public function test_evaluation_weight_accepts_float_rounding_to_100(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->call('addEvaluationGroup')
            ->set('evaluations.0.class_type', 'LEC')
            ->call('addEvaluationActivity', 0)
            ->set('evaluations.0.activities.0.activity', 'A')
            ->set('evaluations.0.activities.0.weight', 33.33)
            ->call('addEvaluationActivity', 0)
            ->set('evaluations.0.activities.1.activity', 'B')
            ->set('evaluations.0.activities.1.weight', 33.33)
            ->call('addEvaluationActivity', 0)
            ->set('evaluations.0.activities.2.activity', 'C')
            ->set('evaluations.0.activities.2.weight', 33.34)
            ->call('save')
            ->assertHasNoErrors(['evaluations.0.activities']);
    }

    public function test_rubric_matrix_save_persists_correct_pairs(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->call('addLearningOutcome')
            ->set('learningOutcomes.0.code', 'LO1')
            ->set('learningOutcomes.0.description', 'Outcome one')
            ->call('addProficiencyLevel')
            ->set('rubricProficiencyLevels.0.label', 'Excellent')
            ->set('rubricProficiencyLevels.0.score_min', 80)
            ->set('rubricProficiencyLevels.0.score_max', 100)
            ->call('addKeyIndicator')
            ->set('rubricKeyIndicators.0.learning_outcome_index', 0)
            ->set('rubricKeyIndicators.0.code', '1.1')
            ->set('rubricKeyIndicators.0.description', 'Demonstrates mastery')
            ->set('rubricCells.0.0', 'Achieves excellent mastery')
            ->call('save')
            ->assertSet('editing', false);

        $keyIndicator = SyllabusRubricKeyIndicator::where('code', '1.1')->firstOrFail();
        $proficiencyLevel = SyllabusRubricProficiencyLevel::where('label', 'Excellent')->firstOrFail();

        $this->assertDatabaseHas('syllabus_rubric_cells', [
            'rubric_key_indicator_id' => $keyIndicator->id,
            'rubric_proficiency_level_id' => $proficiencyLevel->id,
            'description' => 'Achieves excellent mastery',
        ]);
    }

    public function test_media_can_attach_to_different_sections_without_leakage(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $itemA = MediaLibraryItem::factory()->for($this->school)->create();
        $itemB = MediaLibraryItem::factory()->for($this->school)->create();

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->set('selectedMaterialIds.course_description', [$itemA->id])
            ->set('selectedMaterialIds.video_overview', [$itemB->id])
            ->call('save')
            ->assertSet('editing', false);

        $syllabus = Syllabus::where('course_id', $this->course->id)->firstOrFail();

        $this->assertDatabaseHas('syllabus_materials', [
            'syllabus_id' => $syllabus->id,
            'section' => 'course_description',
            'media_library_item_id' => $itemA->id,
        ]);
        $this->assertDatabaseHas('syllabus_materials', [
            'syllabus_id' => $syllabus->id,
            'section' => 'video_overview',
            'media_library_item_id' => $itemB->id,
        ]);
        $this->assertDatabaseMissing('syllabus_materials', [
            'syllabus_id' => $syllabus->id,
            'section' => 'video_overview',
            'media_library_item_id' => $itemA->id,
        ]);
    }

    public function test_same_media_item_can_attach_to_two_sections(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        $item = MediaLibraryItem::factory()->for($this->school)->create();

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->set('selectedMaterialIds.course_description', [$item->id])
            ->set('selectedMaterialIds.video_overview', [$item->id])
            ->call('save')
            ->assertSet('editing', false);

        $syllabus = Syllabus::where('course_id', $this->course->id)->firstOrFail();

        $this->assertDatabaseHas('syllabus_materials', [
            'syllabus_id' => $syllabus->id,
            'section' => 'course_description',
            'media_library_item_id' => $item->id,
        ]);
        $this->assertDatabaseHas('syllabus_materials', [
            'syllabus_id' => $syllabus->id,
            'section' => 'video_overview',
            'media_library_item_id' => $item->id,
        ]);
        $this->assertDatabaseCount('syllabus_materials', 2);
    }

    public function test_evaluation_activity_learning_outcome_pivot_matches_submitted_checkboxes(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->call('addLearningOutcome')
            ->set('learningOutcomes.0.code', 'LO1')
            ->set('learningOutcomes.0.description', 'First outcome')
            ->call('addLearningOutcome')
            ->set('learningOutcomes.1.code', 'LO2')
            ->set('learningOutcomes.1.description', 'Second outcome')
            ->call('addEvaluationGroup')
            ->set('evaluations.0.class_type', 'LEC')
            ->call('addEvaluationActivity', 0)
            ->set('evaluations.0.activities.0.activity', 'Quiz')
            ->set('evaluations.0.activities.0.weight', 100)
            ->set('evaluations.0.activities.0.learning_outcome_indices', [1])
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

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->assertCount('learningOutcomes', 2)
            ->call('removeLearningOutcome', 1)
            ->call('save')
            ->assertSet('editing', false);

        $this->assertDatabaseMissing('syllabus_learning_outcomes', ['code' => 'LO2']);
        $this->assertDatabaseHas('syllabus_learning_outcomes', ['code' => 'LO1']);
        $this->assertDatabaseCount('syllabus_learning_outcomes', 1);
    }

    public function test_cancel_edit_exits_edit_mode_without_saving(): void
    {
        $this->teacher->givePermissionTo(['syllabus.view', 'syllabus.edit']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->call('edit')
            ->assertSet('editing', true)
            ->call('cancelEdit')
            ->assertSet('editing', false);

        $this->assertDatabaseCount('syllabuses', 0);
    }
}
