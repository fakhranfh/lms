<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentType;
use App\Livewire\Courses\AssessmentForm;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentFormTest extends TestCase
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

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'personal'])
            ->assertStatus(403);
    }

    public function test_default_weight_is_prefilled_per_type(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'personal'])
            ->assertSet('weight', (string) AssessmentType::TheoryPersonalAssignment->defaultWeight());

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'team'])
            ->assertSet('weight', (string) AssessmentType::TheoryTeamAssignment->defaultWeight());
    }

    public function test_question_repeater_add_and_remove(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'personal'])
            ->assertCount('questions', 1)
            ->call('addQuestion')
            ->assertCount('questions', 2)
            ->call('removeQuestion', 0)
            ->assertCount('questions', 1);
    }

    public function test_creates_personal_assignment_with_questions(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'personal'])
            ->set('title', 'Essay Assignment')
            ->set('weight', '25')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'Write an essay about Laravel.')
            ->set('questions.0.points', '100')
            ->call('save')
            ->assertRedirect(route('assessments.index', $this->course));

        $this->assertDatabaseHas('assessments', [
            'course_id' => $this->course->id,
            'title' => 'Essay Assignment',
            'type' => AssessmentType::TheoryPersonalAssignment->value,
            'assigned_to' => AssessmentAssignedTo::Individual->value,
        ]);

        $assessment = Assessment::where('title', 'Essay Assignment')->firstOrFail();
        $this->assertDatabaseHas('assessment_questions', [
            'assessment_id' => $assessment->id,
            'points' => 100,
        ]);
    }

    public function test_creates_team_assignment_with_group_assigned_to(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'team'])
            ->set('title', 'Group Project')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'Build something together.')
            ->set('questions.0.points', '50')
            ->call('save');

        $this->assertDatabaseHas('assessments', [
            'title' => 'Group Project',
            'type' => AssessmentType::TheoryTeamAssignment->value,
            'assigned_to' => AssessmentAssignedTo::Group->value,
        ]);
    }

    public function test_validation_requires_end_date_after_start_date(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'personal'])
            ->set('title', 'Bad Dates')
            ->set('startDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'x')
            ->set('questions.0.points', '10')
            ->call('save')
            ->assertHasErrors(['endDate']);
    }

    public function test_media_attachment_is_synced_to_question(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        $material = MediaLibraryItem::factory()->for($this->school)->create();

        Livewire::test(AssessmentForm::class, ['course' => $this->course, 'type' => 'personal'])
            ->set('title', 'With Attachment')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'See attached file.')
            ->set('questions.0.points', '10')
            ->call('toggleQuestionMaterial', 0, $material->id)
            ->call('save');

        $assessment = Assessment::where('title', 'With Attachment')->firstOrFail();
        $question = $assessment->questions->first();

        $this->assertTrue($question->files->contains($material));
    }

    public function test_editing_preserves_question_ids_without_duplicating(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);
        $question = AssessmentQuestion::factory()->for($assessment)->create();

        Livewire::test(AssessmentForm::class, ['assessment' => $assessment])
            ->assertSet('questions.0.id', $question->id)
            ->set('questions.0.description', 'Updated description')
            ->call('save');

        $this->assertEquals(1, $assessment->questions()->count());
        $this->assertDatabaseHas('assessment_questions', [
            'id' => $question->id,
            'description' => 'Updated description',
        ]);
    }
}
