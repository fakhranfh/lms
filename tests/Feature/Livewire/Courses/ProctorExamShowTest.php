<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSessionStatus;
use App\Enums\RoleName;
use App\Jobs\FinalizeExamSubmissionJob;
use App\Jobs\FinalizeProctorDisqualificationJob;
use App\Livewire\Courses\ProctorExamShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionOption;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\ExamReferenceFile;
use App\Models\FinalExam;
use App\Models\MediaLibraryItem;
use App\Models\Period;
use App\Models\ProctorSession;
use App\Models\ProctorSnapshot;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use App\Services\ProctorSessionService;
use App\Services\R2StorageService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;
use Tests\TestCase;

class ProctorExamShowTest extends TestCase
{
    private School $school;

    private User $student;

    private Course $course;

    private Assessment $assessment;

    private AssessmentQuestion $mcQuestion;

    private AssessmentQuestionOption $correctOption;

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
            'attempt_limit' => 2,
        ]);

        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        $this->mcQuestion = AssessmentQuestion::factory()->for($this->assessment)->create([
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        $this->correctOption = AssessmentQuestionOption::factory()->for($this->mcQuestion, 'question')->create(['is_correct' => true, 'order' => 1]);
        AssessmentQuestionOption::factory()->for($this->mcQuestion, 'question')->create(['is_correct' => false, 'order' => 2]);
    }

    /**
     * Mirrors ProctorPreflightShow marking all checks passed, which is what
     * flips the preflight-passed cache flag startAttempt() relies on.
     */
    private function passPreflight(?Assessment $assessment = null): void
    {
        app(ProctorSessionService::class)->markPreflightPassed(($assessment ?? $this->assessment)->id, $this->student->id);
    }

    public function test_start_attempt_creates_attempt_and_proctor_session(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();

        $this->assertDatabaseHas('proctor_sessions', [
            'assessment_attempt_id' => $attempt->id,
            'status' => ProctorSessionStatus::Active->value,
        ]);
    }

    public function test_start_attempt_is_blocked_without_preflight(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertForbidden();

        $this->assertDatabaseMissing('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_start_attempt_skips_preflight_check_in_local_environment(): void
    {
        $this->app['env'] = 'local';

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_log_proctor_event_persists_event(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

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

    public function test_begin_submission_flips_session_to_submitting_and_dispatches_job(): void
    {
        Bus::fake();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->call('beginSubmission');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();

        $this->assertNull($attempt->submitted_at);
        $this->assertTrue(app(ProctorSessionService::class)->isSubmitting($session->id));

        Bus::assertDispatched(FinalizeExamSubmissionJob::class, fn ($job) => $job->attemptId === $attempt->id);

        // beginSubmission() is #[Renderless] so the calling tab's own DOM
        // isn't morphed mid-cleanup; a fresh mount (e.g. after the client's
        // own reload, or an actual refresh) is what shows the processing
        // screen, and that's what this asserts.
        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Submitting your exam, please wait…');
    }

    public function test_begin_submission_is_idempotent_when_already_submitting(): void
    {
        Bus::fake();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->call('beginSubmission')
            ->call('beginSubmission');

        Bus::assertDispatchedTimes(FinalizeExamSubmissionJob::class, 1);
    }

    public function test_finalize_exam_submission_job_scores_answers_and_completes_session(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id);

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();

        app()->call([app(FinalizeExamSubmissionJob::class, ['attemptId' => $attempt->id]), 'handle']);

        $attempt->refresh();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();

        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(ProctorSessionStatus::Completed, $session->status);
        $this->assertNotNull($session->ended_at);

        $this->assertDatabaseHas('assessment_question_answers', [
            'assessment_attempt_id' => $attempt->id,
            'assessment_question_id' => $this->mcQuestion->id,
            'selected_option_id' => $this->correctOption->id,
            'score' => 10,
        ]);

        // A fully multiple-choice exam is entirely auto-graded — no teacher
        // review is required before the score counts.
        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 10,
        ]);
    }

    public function test_finalize_exam_submission_job_leaves_score_pending_when_essay_question_is_ungraded(): void
    {
        $essayQuestion = AssessmentQuestion::factory()->for($this->assessment)->create([
            'question_type' => 'essay',
            'points' => 5,
            'order' => 2,
        ]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id)
            ->set("answers.{$essayQuestion->id}", '<p>My essay answer.</p>');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();

        app()->call([app(FinalizeExamSubmissionJob::class, ['attemptId' => $attempt->id]), 'handle']);

        $this->assertDatabaseHas('assessment_question_answers', [
            'assessment_attempt_id' => $attempt->id,
            'assessment_question_id' => $essayQuestion->id,
            'answer_text' => '<p>My essay answer.</p>',
            'score' => null,
        ]);

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
        ]);
    }

    public function test_refreshing_while_submission_is_pending_shows_processing_screen_not_the_exam(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();
        app(ProctorSessionService::class)->markSubmitting($session->id);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Submitting your exam, please wait…')
            ->assertDontSee('Submit Exam');
    }

    public function test_record_snapshot_uploaded_promotes_file_out_of_temp_folder(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $tempKey = 'schools/demo/temp/proctor/session/abc123-screenshot.jpg';
        $finalKey = 'schools/demo/proctor/session/abc123-screenshot.jpg';

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('promoteFromTemp')->once()->with($tempKey, $finalKey);

        $this->passPreflight();

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $instance->recordSnapshotUploaded($r2Mock, 'screen', $tempKey);

        $this->assertDatabaseHas('proctor_snapshots', [
            'type' => 'screen',
            'file_url' => $finalKey,
        ]);
    }

    public function test_request_snapshot_upload_url_still_works_right_after_submission_completes(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        // Mirrors finishSubmit()'s real sequence: beginSubmission() finishes
        // (marking the attempt submitted and the session Completed) before
        // the client's MediaRecorder flushes its trailing chunk, which
        // triggers one more requestSnapshotUploadUrl() call for a session
        // that's no longer "in progress".
        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->call('beginSubmission');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();
        $this->assertSame(ProctorSessionStatus::Completed, $session->fresh()->status);

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])->instance();

        $result = $instance->requestSnapshotUploadUrl(app(R2StorageService::class), 'webcam-recording-123.webm', 'Video');

        $this->assertStringContainsString((string) $session->id, $result['key']);
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

        $this->passPreflight();

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

        $this->passPreflight();

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

        $this->passPreflight();

        $instance = Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->instance();

        $instance->recordSnapshotUploaded($r2Mock, 'screen', $tempKey, null, now()->subHours(2)->toIso8601String());

        $snapshot = ProctorSnapshot::where('file_url', $finalKey)->firstOrFail();
        $this->assertTrue($snapshot->captured_at->greaterThan(now()->subMinute()));
    }

    public function test_begin_disqualification_flips_session_to_submitting_and_dispatches_job(): void
    {
        Bus::fake();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->call('beginDisqualification', 'reading_suspected');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();

        $this->assertNull($attempt->submitted_at);
        $this->assertTrue(app(ProctorSessionService::class)->isSubmitting($session->id));

        Bus::assertDispatched(FinalizeProctorDisqualificationJob::class, fn ($job) => $job->attemptId === $attempt->id && $job->reason === 'reading_suspected');

        // beginDisqualification() is #[Renderless] — see the equivalent note
        // on the submission test above.
        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Submitting your exam, please wait…');
    }

    public function test_begin_disqualification_is_idempotent_when_already_submitting(): void
    {
        Bus::fake();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->call('beginDisqualification', 'reading_suspected')
            ->call('beginDisqualification', 'tab_switch');

        Bus::assertDispatchedTimes(FinalizeProctorDisqualificationJob::class, 1);
    }

    public function test_finalize_proctor_disqualification_job_terminates_session_and_zeroes_score(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();

        app()->call([app(FinalizeProctorDisqualificationJob::class, [
            'attemptId' => $attempt->id,
            'reason' => 'reading_suspected',
        ]), 'handle']);

        $attempt->refresh();
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
    }

    public function test_refreshing_while_disqualification_is_submitting_shows_processing_screen_not_the_exam(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt');

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();
        $session = ProctorSession::where('assessment_attempt_id', $attempt->id)->firstOrFail();
        app(ProctorSessionService::class)->markSubmitting($session->id);

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Submitting your exam, please wait…')
            ->assertDontSee('Submit Exam');
    }

    public function test_open_book_attempt_view_exposes_open_book_to_alpine_gating(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::OpenBook)
            ->assertSeeHtml("allowedTypes: 'open_book'")
            ->assertSeeHtml('if (! document.fullscreenElement) { document.documentElement.requestFullscreen')
            ->assertSeeHtml("handleViolation('navigation_attempt'")
            ->assertSeeHtml("['t', 'n'].includes(e.key.toLowerCase())")
            ->assertSeeHtml("anchor.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey");
    }

    public function test_open_book_attempt_view_exposes_course_materials_for_alpine(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Reference Sheet']);
        $session->materials()->attach($material->id, ['order' => 1]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::OpenBook)
            ->assertSeeHtml('Reference Sheet');
    }

    public function test_open_book_attempt_view_exposes_reference_files_for_alpine(): void
    {
        ExamReferenceFile::factory()->for($this->assessment)->for($this->student)->create(['title' => 'My Cheat Sheet.pdf']);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::OpenBook)
            ->assertSeeHtml('My Cheat Sheet.pdf');
    }

    public function test_open_book_attempt_view_only_exposes_the_students_own_reference_files(): void
    {
        $otherStudent = User::factory()->forSchool($this->school)->create();
        ExamReferenceFile::factory()->for($this->assessment)->for($otherStudent)->create(['title' => 'Someone Elses Notes.pdf']);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->assertDontSeeHtml('Someone Elses Notes.pdf');
    }

    public function test_closed_book_attempt_view_does_not_expose_course_materials(): void
    {
        $closedBookAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
            'attempt_limit' => 2,
        ]);
        $period = Period::factory()->for($this->course)->create(['order' => 99]);
        FinalExam::factory()->for($closedBookAssessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);
        $question = AssessmentQuestion::factory()->for($closedBookAssessment)->create([
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        AssessmentQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true, 'order' => 1]);

        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Reference Sheet']);
        $session->materials()->attach($material->id, ['order' => 1]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight($closedBookAssessment);

        Livewire::test(ProctorExamShow::class, ['assessment' => $closedBookAssessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::ClosedBook)
            ->assertSeeHtml('examMaterials: [],')
            ->assertDontSeeHtml('Reference Sheet');
    }

    public function test_closed_book_attempt_view_auto_requests_fullscreen(): void
    {
        $closedBookAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
            'attempt_limit' => 2,
        ]);
        $period = Period::factory()->for($this->course)->create(['order' => 98]);
        FinalExam::factory()->for($closedBookAssessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);
        $question = AssessmentQuestion::factory()->for($closedBookAssessment)->create([
            'question_type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
        ]);
        AssessmentQuestionOption::factory()->for($question, 'question')->create(['is_correct' => true, 'order' => 1]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight($closedBookAssessment);

        Livewire::test(ProctorExamShow::class, ['assessment' => $closedBookAssessment])
            ->call('startAttempt')
            ->assertSet('examType', FinalExamType::ClosedBook)
            ->assertSeeHtml('if (! document.fullscreenElement) { document.documentElement.requestFullscreen');
    }

    public function test_answer_selection_is_persisted_to_cache_and_restored_on_remount(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $this->passPreflight();

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->call('startAttempt')
            ->set("answers.{$this->mcQuestion->id}", $this->correctOption->id);

        $attempt = AssessmentAttempt::where('assessment_id', $this->assessment->id)->where('user_id', $this->student->id)->firstOrFail();

        $this->assertSame(
            (string) $this->correctOption->id,
            Redis::hget("proctor_exam_answers:{$attempt->id}", (string) $this->mcQuestion->id)
        );

        Livewire::test(ProctorExamShow::class, ['assessment' => $this->assessment])
            ->assertSet("answers.{$this->mcQuestion->id}", (string) $this->correctOption->id);
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

    public function test_open_book_exam_without_questions_returns_404(): void
    {
        $emptyAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
        ]);
        $period = Period::factory()->for($this->course)->create(['order' => 97]);
        FinalExam::factory()->for($emptyAssessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(ProctorExamShow::class, ['assessment' => $emptyAssessment])
            ->assertStatus(404);
    }
}
