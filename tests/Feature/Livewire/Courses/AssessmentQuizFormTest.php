<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Livewire\Courses\AssessmentQuizForm;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentQuizFormTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
        $this->session = Session::factory()->for($this->course)->create();

        $this->actingAs($this->teacher);
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_default_weight_is_prefilled(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->assertSet('weight', (string) AssessmentType::TheoryQuiz->defaultWeight());
    }

    public function test_question_repeater_add_and_remove(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->assertCount('questions', 1)
            ->call('addQuestion')
            ->assertCount('questions', 2)
            ->call('removeQuestion', 0)
            ->assertCount('questions', 1);
    }

    public function test_new_question_defaults_to_two_empty_options(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->assertCount('questions.0.options', 2)
            ->assertSet('questions.0.options.0.label', '')
            ->assertSet('questions.0.options.1.label', '');
    }

    public function test_creates_quiz_with_multiple_choice_question(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->set('title', 'Chapter 1 Quiz')
            ->set('sessionId', $this->session->id)
            ->set('totalAttempts', '2')
            ->set('scoringMethod', 'highest')
            ->set('timeLimitPerAttempt', '30')
            ->set('questions.0.description', 'What is 2 + 2?')
            ->set('questions.0.points', '10')
            ->set('questions.0.options.0.label', '4')
            ->set('questions.0.options.1.label', '5')
            ->call('toggleCorrect', 0, 0)
            ->call('save')
            ->assertRedirect(route('assessments.index', $this->course));

        $this->assertDatabaseHas('assessments', [
            'course_id' => $this->course->id,
            'title' => 'Chapter 1 Quiz',
            'type' => AssessmentType::TheoryQuiz->value,
            'session_id' => $this->session->id,
        ]);

        $assessment = Assessment::where('title', 'Chapter 1 Quiz')->firstOrFail();
        $this->assertTrue($assessment->start_date->equalTo($this->session->date_start));
        $this->assertTrue($assessment->end_date->equalTo($this->session->date_end));

        $this->assertDatabaseHas('quizzes', [
            'assessment_id' => $assessment->id,
            'total_attempts' => 2,
            'total_question' => 1,
            'time_limit_per_attempt' => 30,
        ]);

        $question = QuizQuestion::where('description', 'What is 2 + 2?')->firstOrFail();
        $this->assertEquals(2, $question->options()->count());
        $this->assertEquals(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_multiple_choice_requires_exactly_one_correct_option(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->set('title', 'Bad Quiz')
            ->set('sessionId', $this->session->id)
            ->set('questions.0.description', 'Pick one.')
            ->set('questions.0.points', '10')
            ->set('questions.0.options.0.label', 'A')
            ->set('questions.0.options.1.label', 'B')
            ->call('save')
            ->assertHasErrors(['questions.0.options']);

        $this->assertDatabaseMissing('assessments', ['title' => 'Bad Quiz']);
    }

    public function test_session_is_required(): void
    {
        $this->teacher->givePermissionTo('assessment.create');

        Livewire::test(AssessmentQuizForm::class, ['course' => $this->course])
            ->set('title', 'No Session Quiz')
            ->set('questions.0.description', 'Q1')
            ->set('questions.0.points', '10')
            ->set('questions.0.options.0.label', 'A')
            ->set('questions.0.options.1.label', 'B')
            ->call('toggleCorrect', 0, 0)
            ->call('save')
            ->assertHasErrors(['sessionId']);
    }

    public function test_editing_preserves_question_and_option_ids(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $assessment = Assessment::factory()->for($this->course)->for($this->session)->create([
            'type' => AssessmentType::TheoryQuiz,
        ]);
        $quiz = Quiz::factory()->for($assessment)->create();
        $question = QuizQuestion::factory()->for($quiz)->create(['question_type' => 'multiple_choice']);
        QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true]);
        QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => false]);

        Livewire::test(AssessmentQuizForm::class, ['assessment' => $assessment])
            ->assertSet('questions.0.id', $question->id)
            ->set('questions.0.description', 'Updated description')
            ->call('save');

        $this->assertEquals(1, $quiz->questions()->count());
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $question->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_wrong_type_edit_returns_404(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        Livewire::test(AssessmentQuizForm::class, ['assessment' => $assessment])
            ->assertStatus(404);
    }
}
