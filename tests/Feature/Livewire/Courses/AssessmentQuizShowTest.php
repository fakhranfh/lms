<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\QuizScoringMethod;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentQuizShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentQuizShowTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Assessment $assessment;

    private Quiz $quiz;

    private QuizQuestion $mcQuestion;

    private QuizQuestionOption $correctOption;

    private QuizQuestionOption $wrongOption;

    private QuizQuestion $essayQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryQuiz,
            'end_date' => now()->addWeek(),
        ]);

        $this->quiz = Quiz::factory()->for($this->assessment)->create([
            'total_attempts' => 2,
            'scoring_method' => QuizScoringMethod::Highest,
            'time_limit_per_attempt' => null,
        ]);

        $this->mcQuestion = QuizQuestion::factory()->for($this->quiz)->create([
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        $this->correctOption = QuizQuestionOption::factory()->for($this->mcQuestion, 'question')->create(['is_correct' => true, 'order' => 1]);
        $this->wrongOption = QuizQuestionOption::factory()->for($this->mcQuestion, 'question')->create(['is_correct' => false, 'order' => 2]);

        $this->essayQuestion = QuizQuestion::factory()->for($this->quiz)->create([
            'question_type' => 'essay',
            'points' => 20,
            'order' => 2,
        ]);
    }

    public function test_student_can_start_and_submit_attempt_with_auto_scoring(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->set("answers.{$this->essayQuestion->id}", 'My essay answer')
            ->call('submitAttempt');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
            'attempt_number' => 1,
        ]);

        $this->assertDatabaseHas('assessment_quiz_answers', [
            'quiz_question_id' => $this->mcQuestion->id,
            'selected_option_id' => $this->correctOption->id,
            'score' => 10,
        ]);

        $this->assertDatabaseHas('assessment_quiz_answers', [
            'quiz_question_id' => $this->essayQuestion->id,
            'answer_text' => 'My essay answer',
            'score' => null,
        ]);

        $this->assertDatabaseHas('assessment_scores', [
            'score' => 10,
        ]);
    }

    public function test_incorrect_mc_answer_scores_zero(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->wrongOption->id)
            ->call('submitAttempt');

        $this->assertDatabaseHas('assessment_quiz_answers', [
            'quiz_question_id' => $this->mcQuestion->id,
            'selected_option_id' => $this->wrongOption->id,
            'score' => 0,
        ]);
    }

    public function test_attempt_cap_blocks_new_attempt(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
        ]);
        AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 2,
        ]);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertSee('maximum number of attempts');

        $this->assertEquals(2, AssessmentAttempt::where('assessment_id', $this->assessment->id)->count());
    }

    public function test_scoring_method_highest_picks_best_attempt(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $component = Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment]);

        $component->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->wrongOption->id)
            ->set("answers.{$this->essayQuestion->id}", 'weak answer')
            ->call('submitAttempt');

        $component->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->set("answers.{$this->essayQuestion->id}", 'strong answer')
            ->call('submitAttempt');

        $this->assertDatabaseHas('assessment_scores', ['score' => 10]);
    }

    public function test_attempt_history_is_ordered_most_recent_first(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $component = Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment]);

        $component->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->call('submitAttempt');

        $component->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->wrongOption->id)
            ->call('submitAttempt');

        $attemptNumbers = $component->viewData('attemptRows')->map(fn ($row) => $row['attempt']->attempt_number)->all();

        $this->assertSame([2, 1], $attemptNumbers);
    }

    public function test_essay_answer_stays_ungraded_and_is_excluded_from_the_live_score(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->set("answers.{$this->essayQuestion->id}", 'My essay')
            ->call('submitAttempt');

        $this->assertDatabaseHas('assessment_quiz_answers', [
            'quiz_question_id' => $this->essayQuestion->id,
            'answer_text' => 'My essay',
            'score' => null,
        ]);

        $this->assertDatabaseHas('assessment_scores', ['score' => 10]);
    }

    public function test_student_without_submit_permission_forbidden(): void
    {
        $this->student->givePermissionTo(['assessment.view']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertStatus(403);
    }

    public function test_question_description_renders_as_html(): void
    {
        $this->mcQuestion->update(['description' => '<p>What is <strong>2 + 2</strong>?</p>']);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertSeeHtml('<strong>2 + 2</strong>');
    }

    public function test_in_progress_attempt_with_time_limit_exposes_a_countdown_deadline(): void
    {
        $this->quiz->update(['time_limit_per_attempt' => 20]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $component = Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $deadline = $attempt->started_at->copy()->addMinutes(20)->toIso8601String();

        $component->assertSeeHtml($deadline);
    }

    public function test_in_progress_attempt_without_time_limit_shows_no_countdown(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertDontSeeHtml('material-symbols-outlined text-[18px]">timer');
    }

    public function test_submit_attempt_clamps_to_the_time_limit_when_overdue(): void
    {
        $this->quiz->update(['time_limit_per_attempt' => 10]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => null,
        ]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->call('submitAttempt');

        $expectedDeadline = $attempt->started_at->copy()->addMinutes(10);

        $this->assertTrue($attempt->fresh()->submitted_at->equalTo($expectedDeadline));
    }

    public function test_draft_quiz_is_inaccessible_to_students(): void
    {
        $this->assessment->update(['status' => AssessmentStatus::Draft]);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->assertStatus(404);
    }

    public function test_draft_quiz_remains_accessible_to_teacher(): void
    {
        $this->assessment->update(['status' => AssessmentStatus::Draft]);

        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $this->assessment])
            ->assertStatus(200);
    }

    public function test_wrong_type_returns_404(): void
    {
        $personalAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentQuizShow::class, ['assessment' => $personalAssessment])
            ->assertStatus(404);
    }
}
