<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Livewire\Courses\AssessmentFinalExamForm;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentFinalExamFormTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    private Period $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
        $this->period = Period::factory()->for($this->course)->create();

        $this->actingAs($this->teacher);
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_default_weight_is_prefilled(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->assertSet('weight', (string) AssessmentType::TheoryFinalExam->defaultWeight())
            ->assertCount('questions', 1);
    }

    public function test_creates_final_exam_with_question(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->set('title', 'Final Exam A')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('periodId', $this->period->id)
            ->set('examType', FinalExamType::OpenBook->value)
            ->set('allowLocalFiles', true)
            ->set('allowInternet', true)
            ->set('questions.0.description', 'Explain the theory.')
            ->set('questions.0.points', '100')
            ->call('save')
            ->assertRedirect(route('assessments.index', $this->course));

        $this->assertDatabaseHas('assessments', [
            'course_id' => $this->course->id,
            'title' => 'Final Exam A',
            'type' => AssessmentType::TheoryFinalExam->value,
        ]);

        $assessment = Assessment::where('title', 'Final Exam A')->firstOrFail();

        $this->assertDatabaseHas('final_exams', [
            'assessment_id' => $assessment->id,
            'period_id' => $this->period->id,
            'exam_type' => FinalExamType::OpenBook->value,
            'allow_local_files' => true,
            'allow_internet' => true,
        ]);

        $this->assertEquals(1, $assessment->questions()->count());
    }

    public function test_period_is_required(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->set('title', 'No Period Exam')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'Q1')
            ->set('questions.0.points', '10')
            ->call('save')
            ->assertHasErrors(['periodId']);

        $this->assertDatabaseMissing('assessments', ['title' => 'No Period Exam']);
    }

    public function test_question_repeater_add_and_remove(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->assertCount('questions', 1)
            ->call('addQuestion')
            ->assertCount('questions', 2)
            ->call('removeQuestion', 0)
            ->assertCount('questions', 1);
    }

    public function test_editing_preserves_question_ids_and_final_exam_fields(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $question = AssessmentQuestion::factory()->for($assessment)->create(['points' => 50]);
        $finalExam = FinalExam::factory()->for($assessment)->create([
            'period_id' => $this->period->id,
            'exam_type' => FinalExamType::TakeHome,
            'allow_local_files' => true,
            'allow_internet' => false,
        ]);

        Livewire::test(AssessmentFinalExamForm::class, ['assessment' => $assessment])
            ->assertSet('questions.0.id', $question->id)
            ->assertSet('periodId', $this->period->id)
            ->assertSet('examType', FinalExamType::TakeHome->value)
            ->assertSet('allowLocalFiles', true)
            ->assertSet('allowInternet', false)
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'Updated description')
            ->set('allowInternet', true)
            ->call('save');

        $this->assertEquals(1, $assessment->questions()->count());
        $this->assertDatabaseHas('assessment_questions', [
            'id' => $question->id,
            'description' => 'Updated description',
        ]);
        $this->assertDatabaseHas('final_exams', [
            'id' => $finalExam->id,
            'allow_internet' => true,
        ]);
    }

    public function test_wrong_type_edit_returns_404(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        Livewire::test(AssessmentFinalExamForm::class, ['assessment' => $assessment])
            ->assertStatus(404);
    }
}
