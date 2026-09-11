<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Livewire\Courses\ProctorQuizQuestionsForm;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\Quiz;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProctorQuizQuestionsFormTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);

        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        $this->actingAs($this->teacher);
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(ProctorQuizQuestionsForm::class, ['course' => $this->course, 'assessment' => $this->assessment])
            ->assertStatus(403);
    }

    public function test_standard_final_exam_type_returns_404(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $standardAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $standardPeriod = Period::factory()->for($this->course)->create(['order' => 99]);
        FinalExam::factory()->for($standardAssessment)->create([
            'period_id' => $standardPeriod->id,
            'exam_type' => FinalExamType::TakeHome,
        ]);

        Livewire::test(ProctorQuizQuestionsForm::class, ['course' => $this->course, 'assessment' => $standardAssessment])
            ->assertStatus(404);
    }

    public function test_creates_quiz_with_question_for_open_book_exam(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        Livewire::test(ProctorQuizQuestionsForm::class, ['course' => $this->course, 'assessment' => $this->assessment])
            ->set('questions.0.description', 'What is 2+2?')
            ->set('questions.0.points', '10')
            ->set('questions.0.options.0.label', '4')
            ->set('questions.0.options.1.label', '5')
            ->call('toggleCorrect', 0, 0)
            ->call('save')
            ->assertRedirect(route('assessments.final-exam.show', $this->assessment));

        $this->assertDatabaseHas('quizzes', ['assessment_id' => $this->assessment->id]);
        $this->assertDatabaseHas('assessment_questions', ['description' => 'What is 2+2?', 'points' => 10]);
    }

    public function test_existing_questions_are_prefilled(): void
    {
        $this->teacher->givePermissionTo('assessment.edit');

        $quiz = Quiz::factory()->for($this->assessment)->create();

        Livewire::test(ProctorQuizQuestionsForm::class, ['course' => $this->course, 'assessment' => $this->assessment])
            ->assertSet('totalAttempts', $quiz->total_attempts !== null ? (string) $quiz->total_attempts : '');
    }
}
