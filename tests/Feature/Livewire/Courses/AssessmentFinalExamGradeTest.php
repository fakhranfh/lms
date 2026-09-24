<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorSnapshotType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentFinalExamGrade;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionAnswer;
use App\Models\AssessmentQuestionOption;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\ProctorEvent;
use App\Models\ProctorSession;
use App\Models\ProctorSnapshot;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\R2StorageService;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentFinalExamGradeTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Assessment $assessment;

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
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
        ]);

        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::TakeHome,
        ]);
    }

    public function test_teacher_grade_creates_score_and_student_sees_score_and_feedback(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $q1 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $q2 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 35]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("gradeQuestionScores.{$q1->id}", '50')
            ->set("gradeQuestionScores.{$q2->id}", '35')
            ->set('gradeFeedback', 'Well done')
            ->call('submitGrade')
            ->assertRedirect(route('assessments.final-exam.grade', [$this->assessment, $this->student]));

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 85,
            'feedback' => 'Well done',
        ]);
    }

    public function test_final_score_shown_once_graded(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertDontSee('Final Score');

        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 85, 'feedback' => 'Well done']);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSee('Final Score')
            ->assertSee('85')
            ->assertSee('Well done');
    }

    public function test_missing_score_shows_validation_error(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $question = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('submitGrade')
            ->assertHasErrors(["gradeQuestionScores.{$question->id}"]);
    }

    public function test_mixed_exam_shows_auto_graded_multiple_choice_read_only_and_only_requires_essay_score(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::OpenBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $mcQuestion = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'multiple_choice', 'points' => 10]);
        $correctOption = AssessmentQuestionOption::factory()->for($mcQuestion, 'question')->create(['is_correct' => true]);
        $essayQuestion = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'essay', 'points' => 20]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($mcQuestion, 'question')->create([
            'selected_option_id' => $correctOption->id,
            'score' => 10,
        ]);
        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($essayQuestion, 'question')->create([
            'answer_text' => '<p>My essay answer.</p>',
            'score' => null,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSee('Correct')
            ->assertDontSeeHtml("wire:model=\"gradeQuestionScores.{$mcQuestion->id}\"")
            ->set("gradeQuestionScores.{$essayQuestion->id}", '15')
            ->call('submitGrade')
            ->assertRedirect(route('assessments.final-exam.grade', [$this->assessment, $this->student]));

        // MC score is 10/10 = 100%, essay score is the raw 15 points earned;
        // total is the average of the two: (100 + 15) / 2 = 57.5.
        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 57.5,
        ]);

        $this->assertDatabaseHas('assessment_question_answers', [
            'assessment_attempt_id' => $attempt->id,
            'assessment_question_id' => $essayQuestion->id,
            'score' => 15,
        ]);
    }

    public function test_resaving_mixed_exam_grade_is_blocked_and_does_not_double_count_essay_score(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::OpenBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $mcQuestion = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'multiple_choice', 'points' => 10]);
        $correctOption = AssessmentQuestionOption::factory()->for($mcQuestion, 'question')->create(['is_correct' => true]);
        $essayQuestion = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'essay', 'points' => 20]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($mcQuestion, 'question')->create([
            'selected_option_id' => $correctOption->id,
            'score' => 10,
        ]);
        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($essayQuestion, 'question')->create([
            'answer_text' => '<p>My essay answer.</p>',
            'score' => null,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("gradeQuestionScores.{$essayQuestion->id}", '15')
            ->call('submitGrade');

        $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 57.5]);
        $this->assertDatabaseCount('assessment_scores', 1);

        // A second save attempt must be blocked entirely — not recomputed,
        // not overwritten — since the exam is already graded.
        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("gradeQuestionScores.{$essayQuestion->id}", '18')
            ->call('submitGrade')
            ->assertSee('already been graded and cannot be graded again');

        $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 57.5]);
        $this->assertDatabaseCount('assessment_scores', 1);
    }

    public function test_resaving_take_home_grade_is_blocked(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $question = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("gradeQuestionScores.{$question->id}", '40')
            ->call('submitGrade');

        $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 40]);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("gradeQuestionScores.{$question->id}", '10')
            ->call('submitGrade')
            ->assertSee('already been graded and cannot be graded again');

        $this->assertDatabaseHas('assessment_scores', ['assessment_attempt_id' => $attempt->id, 'score' => 40]);
        $this->assertDatabaseCount('assessment_scores', 1);
    }

    public function test_already_graded_warning_modal_markup_present(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 85]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSeeHtml('confirmRegradeOpen = true')
            ->assertSee('This exam has already been graded');
    }

    public function test_already_reviewed_warning_modal_markup_present(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create([
            'reviewed_at' => now(),
            'reviewed_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSeeHtml('confirmReReviewOpen = true')
            ->assertSee('This proctoring session has already been reviewed')
            ->assertSeeHtml('wire:model="reviewDecision.'.$session->id.'" disabled')
            ->assertSeeHtml('wire:model="reviewNotes.'.$session->id.'" value="" disabled');
    }

    public function test_multiple_choice_shows_student_answer_correct_answer_and_correctness(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::OpenBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $correctQuestion = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'multiple_choice', 'points' => 10, 'order' => 1]);
        $correctOption = AssessmentQuestionOption::factory()->for($correctQuestion, 'question')->create(['label' => 'Paris', 'is_correct' => true]);

        $wrongQuestion = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'multiple_choice', 'points' => 10, 'order' => 2]);
        $wrongCorrectOption = AssessmentQuestionOption::factory()->for($wrongQuestion, 'question')->create(['label' => 'Berlin', 'is_correct' => true]);
        $wrongSelectedOption = AssessmentQuestionOption::factory()->for($wrongQuestion, 'question')->create(['label' => 'Madrid', 'is_correct' => false]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($correctQuestion, 'question')->create([
            'selected_option_id' => $correctOption->id,
            'score' => 10,
        ]);
        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($wrongQuestion, 'question')->create([
            'selected_option_id' => $wrongSelectedOption->id,
            'score' => 0,
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSee('Paris')
            ->assertSee('Correct')
            ->assertSee('Madrid')
            ->assertSee('Berlin')
            ->assertSee('Incorrect')
            ->assertSee('Multiple Choice Score')
            ->assertSee('1 / 2')
            ->assertSee('10 / 20');
    }

    public function test_mc_only_exam_score_is_scaled_to_0_100_and_save_grade_stays_enabled(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::OpenBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $q1 = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'multiple_choice', 'points' => 1]);
        $correct1 = AssessmentQuestionOption::factory()->for($q1, 'question')->create(['is_correct' => true]);

        $q2 = AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'multiple_choice', 'points' => 1]);
        AssessmentQuestionOption::factory()->for($q2, 'question')->create(['is_correct' => true]);
        $wrong2 = AssessmentQuestionOption::factory()->for($q2, 'question')->create(['is_correct' => false]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($q1, 'question')->create([
            'selected_option_id' => $correct1->id,
            'score' => 1,
        ]);
        AssessmentQuestionAnswer::factory()->for($attempt, 'attempt')->for($q2, 'question')->create([
            'selected_option_id' => $wrong2->id,
            'score' => 0,
        ]);

        $this->actingAs($this->teacher);

        // Nothing has auto-finalized the score yet — Save Grade is still
        // enabled precisely so the teacher can attach feedback.
        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set('gradeFeedback', 'Nice try')
            ->call('submitGrade')
            ->assertRedirect(route('assessments.final-exam.grade', [$this->assessment, $this->student]));

        // 1 of 2 questions correct (1 point each) scales to 50 out of 100,
        // not the raw "1".
        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 50,
            'feedback' => 'Nice try',
        ]);
    }

    public function test_review_save_button_disables_while_request_in_flight(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => null]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSeeHtml('wire:target="reviewProctorSession"');
    }

    public function test_without_attempt_returns_404(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertStatus(404);
    }

    public function test_without_grade_permission_is_forbidden(): void
    {
        AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->teacher->givePermissionTo(['assessment.view']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertStatus(403);
    }

    public function test_questions_are_paginated_five_per_page(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::OpenBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        AssessmentQuestion::factory()->for($this->assessment)->count(7)->sequence(
            fn ($sequence) => ['description' => 'Question body '.$sequence->index, 'order' => $sequence->index + 1]
        )->create(['question_type' => AssessmentQuestionType::MultipleChoice]);

        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSee('Question body 0')
            ->assertSee('Question body 4')
            ->assertDontSee('Question body 5')
            ->call('gotoPage', 2, 'questionsPage')
            ->assertSee('Question body 5')
            ->assertSee('Question body 6')
            ->assertDontSee('Question body 0');
    }

    public function test_review_tab_only_shown_for_proctored_exam_types(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['question_type' => 'essay', 'points' => 50]);
        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertDontSee('Review');
    }

    public function test_teacher_sees_event_triggered_screenshots(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
        $event = ProctorEvent::factory()->for($session)->create();
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $event->id,
            'file_url' => 'temp/proctor/'.$session->id.'/screenshot-1.jpg',
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertSee('Preview Screenshots (1)');
    }

    public function test_load_proctor_screenshots_returns_items_grouped_by_event_type(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
        $event = ProctorEvent::factory()->for($session)->create(['event_type' => 'tab_switch']);
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $event->id,
            'file_url' => 'temp/proctor/'.$session->id.'/screenshot-1.jpg',
        ]);

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('getSignedUrl')->once()->andReturn('https://r2.example.com/signed-url');

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshots')
            ->assertReturned(function ($result) {
                return $result['eventTypeOptions'] === ['tab_switch']
                    && count($result['items']) === 1
                    && $result['items'][0]['url'] === 'https://r2.example.com/signed-url'
                    && $result['items'][0]['eventType'] === 'tab_switch';
            });
    }

    public function test_load_proctor_screenshots_pairs_camera_snapshot_from_same_event(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
        $event = ProctorEvent::factory()->for($session)->create(['event_type' => 'tab_switch']);
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $event->id,
            'file_url' => 'temp/proctor/'.$session->id.'/screen-1.jpg',
        ]);
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Webcam,
            'triggered_by_event_id' => $event->id,
            'file_url' => 'temp/proctor/'.$session->id.'/webcam-1.jpg',
        ]);

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('getSignedUrl')->twice()->andReturn('https://r2.example.com/signed-url');

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshots')
            ->assertReturned(function ($result) {
                return count($result['items']) === 1
                    && $result['items'][0]['cameraUrl'] === 'https://r2.example.com/signed-url';
            });
    }

    public function test_load_proctor_screenshots_returns_null_camera_url_when_no_paired_webcam_snapshot(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
        $event = ProctorEvent::factory()->for($session)->create(['event_type' => 'tab_switch']);
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $event->id,
            'file_url' => 'temp/proctor/'.$session->id.'/screen-1.jpg',
        ]);

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('getSignedUrl')->once()->andReturn('https://r2.example.com/signed-url');

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshots')
            ->assertReturned(fn ($result) => $result['items'][0]['cameraUrl'] === null);
    }

    public function test_load_proctor_screenshots_filters_server_side_by_event_type(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
        $tabSwitchEvent = ProctorEvent::factory()->for($session)->create(['event_type' => 'tab_switch']);
        $windowBlurEvent = ProctorEvent::factory()->for($session)->create(['event_type' => 'window_blur']);
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $tabSwitchEvent->id,
            'file_url' => 'temp/proctor/'.$session->id.'/screenshot-1.jpg',
            'captured_at' => now()->subSeconds(2),
        ]);
        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $windowBlurEvent->id,
            'file_url' => 'temp/proctor/'.$session->id.'/screenshot-2.jpg',
            'captured_at' => now()->subSecond(),
        ]);

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('getSignedUrl')->once()->andReturn('https://r2.example.com/signed-url');

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshots', 'tab_switch')
            ->assertReturned(function ($result) {
                return count($result['items']) === 1
                    && $result['items'][0]['eventType'] === 'tab_switch'
                    && $result['eventTypeOptions'] === ['tab_switch', 'window_blur'];
            });
    }

    public function test_load_proctor_screenshots_paginates_five_per_page(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();

        foreach (range(1, 7) as $i) {
            ProctorSnapshot::factory()->for($session)->create([
                'type' => ProctorSnapshotType::Screen,
                'file_url' => 'temp/proctor/'.$session->id.'/screenshot-'.$i.'.jpg',
                'captured_at' => now()->addSeconds($i),
            ]);
        }

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('getSignedUrl')->times(7)->andReturn('https://r2.example.com/signed-url');

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshots', null, 'asc', 0, 5)
            ->assertReturned(fn ($result) => count($result['items']) === 5 && $result['hasMore'] === true);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshots', null, 'asc', 5, 5)
            ->assertReturned(fn ($result) => count($result['items']) === 2 && $result['hasMore'] === false);
    }

    public function test_load_proctor_screenshot_groups_paginates_each_group_independently(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create();
        $tabSwitchEvent = ProctorEvent::factory()->for($session)->create(['event_type' => 'tab_switch']);
        $windowBlurEvent = ProctorEvent::factory()->for($session)->create(['event_type' => 'window_blur']);

        foreach (range(1, 7) as $i) {
            ProctorSnapshot::factory()->for($session)->create([
                'type' => ProctorSnapshotType::Screen,
                'triggered_by_event_id' => $tabSwitchEvent->id,
                'file_url' => 'temp/proctor/'.$session->id.'/tab-'.$i.'.jpg',
                'captured_at' => now()->addSeconds($i),
            ]);
        }

        ProctorSnapshot::factory()->for($session)->create([
            'type' => ProctorSnapshotType::Screen,
            'triggered_by_event_id' => $windowBlurEvent->id,
            'file_url' => 'temp/proctor/'.$session->id.'/blur-1.jpg',
        ]);

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('getSignedUrl')->times(6)->andReturn('https://r2.example.com/signed-url');

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('loadProctorScreenshotGroups', 'asc', 5)
            ->assertReturned(function ($result) {
                $tabGroup = collect($result['groups'])->firstWhere('eventType', 'tab_switch');
                $blurGroup = collect($result['groups'])->firstWhere('eventType', 'window_blur');

                return $tabGroup['total'] === 7
                    && count($tabGroup['items']) === 5
                    && $tabGroup['hasMore'] === true
                    && $blurGroup['total'] === 1
                    && count($blurGroup['items']) === 1
                    && $blurGroup['hasMore'] === false;
            });
    }

    public function test_review_proctor_session_zeroes_score_on_disqualification(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::ClosedBook]);
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => null]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("reviewDecision.{$session->id}", 'disqualified')
            ->call('reviewProctorSession', $session->id);

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 0,
        ]);
        $this->assertDatabaseHas('proctor_sessions', [
            'id' => $session->id,
            'review_decision' => 'disqualified',
        ]);
    }
}
