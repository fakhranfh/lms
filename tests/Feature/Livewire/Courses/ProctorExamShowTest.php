<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorSessionStatus;
use App\Enums\RoleName;
use App\Livewire\Courses\ProctorExamShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\ProctorSession;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProctorExamShowTest extends TestCase
{
    private School $school;

    private User $student;

    private Course $course;

    private Assessment $assessment;

    private Quiz $quiz;

    private QuizQuestion $mcQuestion;

    private QuizQuestionOption $correctOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
        ]);

        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        $this->quiz = Quiz::factory()->for($this->assessment)->create([
            'total_attempts' => 2,
            'time_limit_per_attempt' => null,
        ]);

        $this->mcQuestion = QuizQuestion::factory()->for($this->quiz)->create([
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        $this->correctOption = QuizQuestionOption::factory()->for($this->mcQuestion, 'question')->create(['is_correct' => true, 'order' => 1]);
        QuizQuestionOption::factory()->for($this->mcQuestion, 'question')->create(['is_correct' => false, 'order' => 2]);
    }

    public function test_start_attempt_creates_attempt_and_proctor_session(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();

        $this->assertDatabaseHas('proctor_sessions', [
            'assessment_attempt_id' => $attempt->id,
            'status' => ProctorSessionStatus::Active->value,
        ]);
    }

    public function test_log_proctor_event_persists_event(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->call('logProctorEvent', 'tab_switch', 'medium', ['note' => 'test']);

        $this->assertDatabaseHas('proctor_events', [
            'event_type' => 'tab_switch',
            'severity' => 'medium',
        ]);
    }

    public function test_submit_attempt_completes_proctor_session(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->call('submitAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();

        $this->assertSame(ProctorSessionStatus::Completed, $session->status);
        $this->assertNotNull($session->ended_at);
    }

    public function test_standard_exam_type_returns_404(): void
    {
        $standardCourse = Course::factory()->for($this->school)->create();
        CoursePerson::factory()->for($standardCourse)->student()->create(['user_id' => $this->student->id]);
        $standardAssessment = Assessment::factory()->for($standardCourse)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $standardPeriod = Period::factory()->for($standardCourse)->create();
        FinalExam::factory()->for($standardAssessment)->create([
            'period_id' => $standardPeriod->id,
            'exam_type' => FinalExamType::TakeHome,
        ]);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $standardAssessment])
            ->assertStatus(404);
    }
}
