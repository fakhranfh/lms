<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Livewire\Courses\AssessmentFinalExamForm;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionOption;
use App\Models\Course;
use App\Models\FinalExam;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentFinalExamFormTest extends TestCase
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

    public function test_creates_final_exam_with_essay_question(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->set('title', 'Final Exam A')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('examType', FinalExamType::OpenBook->value)
            ->set('questions.0.questionType', AssessmentQuestionType::Essay->value)
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
            'exam_type' => FinalExamType::OpenBook->value,
        ]);

        $this->assertEquals(1, $assessment->questions()->count());
        $this->assertDatabaseHas('assessment_questions', [
            'assessment_id' => $assessment->id,
            'question_type' => AssessmentQuestionType::Essay->value,
            'points' => 100,
        ]);
    }

    public function test_creates_final_exam_with_multiple_choice_question_without_points(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->set('title', 'Final Exam MC')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('examType', FinalExamType::ClosedBook->value)
            ->set('questions.0.questionType', AssessmentQuestionType::MultipleChoice->value)
            ->set('questions.0.description', 'What is 2 + 2?')
            ->set('questions.0.options', [
                ['id' => null, 'label' => '4', 'isCorrect' => true, 'order' => 1],
                ['id' => null, 'label' => '5', 'isCorrect' => false, 'order' => 2],
            ])
            ->call('save')
            ->assertRedirect(route('assessments.index', $this->course));

        $assessment = Assessment::where('title', 'Final Exam MC')->firstOrFail();

        $question = AssessmentQuestion::where('assessment_id', $assessment->id)->firstOrFail();
        $this->assertSame(AssessmentQuestionType::MultipleChoice, $question->question_type);
        $this->assertSame(0.0, (float) $question->points);
        $this->assertEquals(2, $question->options()->count());
        $this->assertTrue(AssessmentQuestionOption::where('assessment_question_id', $question->id)->where('label', '4')->firstOrFail()->is_correct);
    }

    public function test_multiple_choice_question_requires_two_options_and_one_correct(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->set('title', 'Invalid MC Exam')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.questionType', AssessmentQuestionType::MultipleChoice->value)
            ->set('questions.0.description', 'A question')
            ->set('questions.0.options', [
                ['id' => null, 'label' => 'Only one option', 'isCorrect' => false, 'order' => 1],
            ])
            ->call('save')
            ->assertHasErrors(['questions.0.options']);

        $this->assertDatabaseMissing('assessments', ['title' => 'Invalid MC Exam']);
    }

    public function test_essay_question_requires_points(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->set('title', 'No Points Exam')
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.questionType', AssessmentQuestionType::Essay->value)
            ->set('questions.0.description', 'Q1')
            ->set('questions.0.points', '')
            ->call('save')
            ->assertHasErrors(['questions.0.points']);

        $this->assertDatabaseMissing('assessments', ['title' => 'No Points Exam']);
    }

    public function test_period_field_is_not_part_of_the_form(): void
    {
        $this->assertFalse(property_exists(AssessmentFinalExamForm::class, 'periodId'));
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

    public function test_dev_autofill_generates_fifteen_multiple_choice_and_five_essay_questions(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        $component = Livewire::test(AssessmentFinalExamForm::class, ['course' => $this->course])
            ->call('devAutofill');

        $questions = $component->get('questions');

        $this->assertCount(20, $questions);

        $mcCount = collect($questions)->where('questionType', AssessmentQuestionType::MultipleChoice->value)->count();
        $essayCount = collect($questions)->where('questionType', AssessmentQuestionType::Essay->value)->count();

        $this->assertSame(15, $mcCount);
        $this->assertSame(5, $essayCount);
    }

    public function test_editing_preserves_question_ids_and_final_exam_fields(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $question = AssessmentQuestion::factory()->for($assessment)->create([
            'points' => 50,
            'question_type' => AssessmentQuestionType::Essay,
        ]);
        $finalExam = FinalExam::factory()->for($assessment)->create([
            'period_id' => null,
            'exam_type' => FinalExamType::TakeHome,
        ]);

        Livewire::test(AssessmentFinalExamForm::class, ['assessment' => $assessment])
            ->assertSet('questions.0.id', $question->id)
            ->assertSet('examType', FinalExamType::TakeHome->value)
            ->set('startDate', now()->format('Y-m-d\TH:i'))
            ->set('endDate', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('questions.0.description', 'Updated description')
            ->call('save');

        $this->assertEquals(1, $assessment->questions()->count());
        $this->assertDatabaseHas('assessment_questions', [
            'id' => $question->id,
            'description' => 'Updated description',
        ]);
        $this->assertDatabaseHas('final_exams', [
            'id' => $finalExam->id,
            'exam_type' => FinalExamType::TakeHome->value,
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
