<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorReviewDecision;
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
use App\Models\ProctorSnapshot;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\R2StorageService;
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

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $eventId = $instance->logProctorEvent('tab_switch', 'medium', ['note' => 'test']);

        $this->assertDatabaseHas('proctor_events', [
            'id' => $eventId,
            'event_type' => 'tab_switch',
            'severity' => 'medium',
        ]);
    }

    public function test_submit_attempt_completes_proctor_session(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->call('submitAttempt')
            ->assertSee('Exam Submitted')
            ->assertSee('Back to Exam Overview')
            ->instance();

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();

        $this->assertSame(ProctorSessionStatus::Completed, $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertTrue($instance->justSubmitted);
    }

    public function test_record_snapshot_uploaded_promotes_file_out_of_temp_folder(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $tempKey = 'schools/demo/temp/proctor/session/abc123-screenshot.jpg';
        $finalKey = 'schools/demo/proctor/session/abc123-screenshot.jpg';

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('promoteFromTemp')->once()->with($tempKey, $finalKey);

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $instance->recordSnapshotUploaded($r2Mock, 'screen', $tempKey);

        $this->assertDatabaseHas('proctor_snapshots', [
            'type' => 'screen',
            'file_url' => $finalKey,
        ]);
    }

    public function test_record_snapshot_uploaded_swallows_missing_temp_object_instead_of_500(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $tempKey = 'schools/demo/temp/proctor/session/abc123-screenshot.jpg';
        $finalKey = 'schools/demo/proctor/session/abc123-screenshot.jpg';

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('promoteFromTemp')
            ->once()
            ->with($tempKey, $finalKey)
            ->andThrow(new \Exception('Failed to promote file from temp: NoSuchKey'));

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $instance->recordSnapshotUploaded($r2Mock, 'screen', $tempKey);

        $this->assertDatabaseMissing('proctor_snapshots', [
            'type' => 'screen',
            'file_url' => $finalKey,
        ]);
    }

    public function test_record_snapshot_uploaded_uses_client_captured_at(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $tempKey = 'schools/demo/temp/proctor/session/abc123-screenshot.jpg';
        $finalKey = 'schools/demo/proctor/session/abc123-screenshot.jpg';
        $capturedAt = now()->subSeconds(5);

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('promoteFromTemp')->once()->with($tempKey, $finalKey);

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $instance->recordSnapshotUploaded($r2Mock, 'screen', $tempKey, null, $capturedAt->toIso8601String());

        $this->assertDatabaseHas('proctor_snapshots', [
            'type' => 'screen',
            'file_url' => $finalKey,
            'captured_at' => $capturedAt->toDateTimeString(),
        ]);
    }

    public function test_record_snapshot_uploaded_ignores_out_of_range_captured_at(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $tempKey = 'schools/demo/temp/proctor/session/abc123-screenshot.jpg';
        $finalKey = 'schools/demo/proctor/session/abc123-screenshot.jpg';

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('promoteFromTemp')->once()->with($tempKey, $finalKey);

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $instance->recordSnapshotUploaded($r2Mock, 'screen', $tempKey, null, now()->subHours(2)->toIso8601String());

        $snapshot = ProctorSnapshot::where('file_url', $finalKey)->firstOrFail();
        $this->assertTrue($snapshot->captured_at->greaterThan(now()->subMinute()));
    }

    public function test_disqualify_attempt_terminates_session_and_zeroes_score(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->call('disqualifyAttempt', 'reading_suspected')
            ->assertSee('Disqualified')
            ->assertSee('Back to Exam Overview')
            ->instance();

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();

        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(ProctorSessionStatus::Terminated, $session->status);
        $this->assertSame(ProctorReviewDecision::Disqualified, $session->review_decision);
        $this->assertNotNull($session->reviewed_at);
        $this->assertNotNull($session->ended_at);

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 0,
        ]);

        $this->assertNotNull($instance->errorMessage);
        $this->assertTrue($instance->disqualified);
    }

    public function test_open_book_attempt_view_exposes_open_book_to_alpine_gating(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::OpenBook)
            ->assertSeeHtml("allowedTypes: 'open_book'")
            ->assertSeeHtml("if (allowedTypes === 'closed_book' && ! document.fullscreenElement) { document.documentElement.requestFullscreen")
            ->assertSeeHtml("window.addEventListener('blur', () => handleViolation('window_blur'")
            ->assertSeeHtml("handleViolation('navigation_attempt'")
            ->assertSeeHtml("['t', 'n'].includes(e.key.toLowerCase())")
            ->assertSeeHtml("anchor.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey");
    }

    public function test_closed_book_attempt_view_auto_requests_fullscreen_and_flags_window_blur(): void
    {
        $closedBookAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
        ]);
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($closedBookAssessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);
        $quiz = Quiz::factory()->for($closedBookAssessment)->create([
            'total_attempts' => 2,
            'time_limit_per_attempt' => null,
        ]);
        $question = QuizQuestion::factory()->for($quiz)->create([
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        QuizQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true, 'order' => 1]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $closedBookAssessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::ClosedBook)
            ->assertSeeHtml("if (allowedTypes === 'closed_book' && ! document.fullscreenElement) { document.documentElement.requestFullscreen");
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
