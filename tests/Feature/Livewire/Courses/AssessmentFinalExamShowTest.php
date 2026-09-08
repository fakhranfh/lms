<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorSnapshotType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentFinalExamShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionOption;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\ExamReferenceFile;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\ProctorEvent;
use App\Models\ProctorSession;
use App\Models\ProctorSnapshot;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\AssessmentAttemptService;
use App\Services\ExamReferenceFileService;
use App\Services\FinalExamService;
use App\Services\R2StorageService;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentFinalExamShowTest extends TestCase
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
            'exam_type' => FinalExamType::ClosedBook,
        ]);
    }

    public function test_student_submit_creates_attempt_and_answer(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'My answer')
            ->call('submit');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
            'attempt_number' => 1,
        ]);
        $this->assertDatabaseHas('assessment_answers', ['answer_text' => 'My answer']);
    }

    public function test_student_can_preview_last_submitted_answer_for_take_home_exam(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::TakeHome]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'My take-home answer')
            ->call('submit')
            ->assertSee('View Last Submission')
            ->assertSee('My take-home answer');
    }

    public function test_take_home_exam_ignores_attempt_limit(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::TakeHome]);
        $this->assessment->update(['attempt_limit' => 1]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
            'submitted_at' => now(),
        ]);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Unlimited')
            ->assertDontSee('reached the maximum number of attempts')
            ->set('answerText', 'Second attempt answer')
            ->call('submit');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
            'attempt_number' => 2,
        ]);
    }

    public function test_teacher_sees_paginated_question_list(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::TakeHome]);
        $this->teacher->givePermissionTo(['assessment.view']);

        $questions = AssessmentQuestion::factory()->for($this->assessment)->count(7)->sequence(
            fn ($sequence) => ['description' => 'Question body '.$sequence->index]
        )->create();

        $this->actingAs($this->teacher);

        $component = Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Question body 0')
            ->assertSee('Question body 4')
            ->assertDontSee('Question body 5')
            ->assertDontSee('Question body 6');

        $component->call('gotoPage', 2, 'questionsPage')
            ->assertDontSee('Question body 0')
            ->assertSee('Question body 5')
            ->assertSee('Question body 6');
    }

    public function test_teacher_sees_paginated_question_list_for_proctored_exam(): void
    {
        $this->teacher->givePermissionTo(['assessment.view']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['description' => 'Closed-book question body']);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Closed-book question body');
    }

    public function test_teacher_sees_multiple_choice_options_in_question_list(): void
    {
        $this->teacher->givePermissionTo(['assessment.view']);

        $question = AssessmentQuestion::factory()->for($this->assessment)->create([
            'question_type' => AssessmentQuestionType::MultipleChoice,
        ]);
        AssessmentQuestionOption::factory()->for($question, 'question')->create(['label' => 'Correct option', 'is_correct' => true]);
        AssessmentQuestionOption::factory()->for($question, 'question')->create(['label' => 'Wrong option', 'is_correct' => false]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Correct option')
            ->assertSee('Wrong option');
    }

    public function test_student_does_not_see_question_list_for_non_proctored_exam(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::TakeHome]);
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['description' => 'Take-home question body']);

        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertDontSeeHtml('<h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>');
    }

    public function test_student_does_not_see_question_list_for_proctored_exam(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['description' => 'Proctored question body']);

        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertDontSee('Proctored question body');
    }

    public function test_student_cannot_resubmit_take_home_exam_after_graded(): void
    {
        $this->assessment->finalExam->update(['exam_type' => FinalExamType::TakeHome]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
        ]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create();

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'Revised answer after grading')
            ->call('submit')
            ->assertSee('already been graded');

        $this->assertDatabaseMissing('assessment_attempts', ['attempt_number' => 2]);
    }

    public function test_exam_type_renders(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Closed Book');
    }

    public function test_student_cannot_resubmit_after_graded(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
        ]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create();

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'Too late')
            ->call('submit')
            ->assertSee('already been graded');

        $this->assertDatabaseMissing('assessment_attempts', ['attempt_number' => 2]);
    }

    public function test_student_cannot_submit_after_end_date(): void
    {
        $this->assessment->update(['end_date' => now()->subDay()]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'Late answer')
            ->call('submit')
            ->assertSee('submission window');

        $this->assertDatabaseMissing('assessment_attempts', ['assessment_id' => $this->assessment->id]);
    }

    public function test_student_without_submit_permission_forbidden(): void
    {
        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'My answer')
            ->call('submit')
            ->assertStatus(403);
    }

    public function test_teacher_grade_creates_score_and_student_sees_score_and_feedback(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $q1 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $q2 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 35]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('openGrading', $this->student->id)
            ->set("gradeQuestionScores.{$q1->id}", '50')
            ->set("gradeQuestionScores.{$q2->id}", '35')
            ->set('gradeFeedback', 'Well done')
            ->call('submitGrade');

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 85,
            'feedback' => 'Well done',
        ]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('85')
            ->assertSee('Well done');
    }

    public function test_teacher_sees_pending_review_until_proctor_session_reviewed(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 90]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => null]);

        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Pending Review')
            ->assertDontSee('Score: 90');

        $session->update(['reviewed_at' => now(), 'reviewed_by' => $this->teacher->id]);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Score: 90')
            ->assertDontSee('Pending Review');
    }

    public function test_student_sees_score_feedback_and_proctor_result_once_reviewed(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 90, 'feedback' => 'Well done']);
        ProctorSession::factory()->for($attempt, 'attempt')->create([
            'reviewed_at' => now(),
            'reviewed_by' => $this->teacher->id,
            'review_decision' => 'warning',
            'review_notes' => 'Looked away briefly.',
        ]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Score: 90')
            ->assertSee('Well done')
            ->assertSee('Warning')
            ->assertSee('Looked away briefly.');
    }

    public function test_teacher_sees_event_triggered_screenshots(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Preview Screenshots (1)');
    }

    public function test_load_proctor_screenshots_returns_items_grouped_by_event_type(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshots', $this->student->id)
            ->assertReturned(function ($result) {
                return $result['eventTypeOptions'] === ['tab_switch']
                    && count($result['items']) === 1
                    && $result['items'][0]['url'] === 'https://r2.example.com/signed-url'
                    && $result['items'][0]['eventType'] === 'tab_switch';
            });
    }

    public function test_load_proctor_screenshots_pairs_camera_snapshot_from_same_event(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshots', $this->student->id)
            ->assertReturned(function ($result) {
                return count($result['items']) === 1
                    && $result['items'][0]['cameraUrl'] === 'https://r2.example.com/signed-url';
            });
    }

    public function test_load_proctor_screenshots_returns_null_camera_url_when_no_paired_webcam_snapshot(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshots', $this->student->id)
            ->assertReturned(fn ($result) => $result['items'][0]['cameraUrl'] === null);
    }

    public function test_load_proctor_screenshots_filters_server_side_by_event_type(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshots', $this->student->id, 'tab_switch')
            ->assertReturned(function ($result) {
                return count($result['items']) === 1
                    && $result['items'][0]['eventType'] === 'tab_switch'
                    && $result['eventTypeOptions'] === ['tab_switch', 'window_blur'];
            });
    }

    public function test_load_proctor_screenshots_paginates_five_per_page(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshots', $this->student->id, null, 'asc', 0, 5)
            ->assertReturned(fn ($result) => count($result['items']) === 5 && $result['hasMore'] === true);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshots', $this->student->id, null, 'asc', 5, 5)
            ->assertReturned(fn ($result) => count($result['items']) === 2 && $result['hasMore'] === false);
    }

    public function test_load_proctor_screenshot_groups_paginates_each_group_independently(): void
    {
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

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('loadProctorScreenshotGroups', $this->student->id, 'asc', 5)
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

    public function test_student_sees_continue_exam_for_in_progress_unsubmitted_attempt(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);

        $quiz = Quiz::factory()->for($this->assessment)->create();
        QuizQuestion::factory()->for($quiz)->create(['question_type' => 'multiple_choice', 'order' => 1]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'submitted_at' => null,
        ]);
        ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => null]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Continue Exam')
            ->assertDontSee('pending proctoring review');
    }

    public function test_student_sees_continue_exam_when_attempt_limit_reached_by_unsubmitted_attempt(): void
    {
        $this->assessment->update(['attempt_limit' => 1]);
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);

        $quiz = Quiz::factory()->for($this->assessment)->create();
        QuizQuestion::factory()->for($quiz)->create(['question_type' => 'multiple_choice', 'order' => 1]);

        AssessmentAttempt::factory()->for($this->assessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
            'submitted_at' => null,
        ]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Continue Exam')
            ->assertDontSee('awaiting grading')
            ->assertDontSee('reached the maximum number of attempts');
    }

    public function test_wrong_type_returns_404(): void
    {
        $personalAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $personalAssessment])
            ->assertStatus(404);
    }

    private function makeOpenBookAssessment(): Assessment
    {
        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'end_date' => now()->addWeek(),
        ]);

        $period = Period::factory()->for($this->course)->create(['order' => random_int(100, 10000)]);
        FinalExam::factory()->for($assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        return $assessment;
    }

    public function test_generate_reference_file_upload_url_rejects_invalid_material_type(): void
    {
        $openBookAssessment = $this->makeOpenBookAssessment();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $result = Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])
            ->instance()
            ->generateReferenceFileUploadUrl('notes.exe', 'NotAType', app(FinalExamService::class), app(ExamReferenceFileService::class), app(AssessmentAttemptService::class));

        expect($result)->toHaveKey('error');
    }

    public function test_generate_reference_file_upload_url_rejects_closed_book_exam(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->call('generateReferenceFileUploadUrl', 'notes.pdf', 'PDF')
            ->assertStatus(403);
    }

    public function test_generate_reference_file_upload_url_returns_presigned_url_for_open_book_exam(): void
    {
        $openBookAssessment = $this->makeOpenBookAssessment();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $instance = Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])->instance();

        $result = $instance->generateReferenceFileUploadUrl('notes.pdf', 'PDF', app(FinalExamService::class), app(ExamReferenceFileService::class), app(AssessmentAttemptService::class));

        $this->assertArrayHasKey('key', $result);
        $this->assertStringContainsString((string) $openBookAssessment->id, $result['key']);
        $this->assertStringContainsString((string) $this->student->id, $result['key']);
    }

    public function test_finalize_reference_file_upload_persists_record_and_returns_payload(): void
    {
        $openBookAssessment = $this->makeOpenBookAssessment();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $tempKey = "schools/demo/temp/exam-reference/{$openBookAssessment->id}/{$this->student->id}/abc123-notes.pdf";

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('verifyFileExists')->once()->with($tempKey)->andReturn(['exists' => true, 'size' => 1024, 'mime_type' => 'application/pdf']);
        $r2Mock->shouldReceive('downloadToLocalTemp')->once()->with($tempKey)->andReturn(sys_get_temp_dir().'/fake-notes.pdf');
        $r2Mock->shouldReceive('validateFileContent')->once();
        $r2Mock->shouldReceive('validateMimeType')->once();
        $r2Mock->shouldReceive('schoolPrefix')->once()->andReturn('schools/demo/');
        $r2Mock->shouldReceive('promoteFromTemp')->once();
        $r2Mock->shouldReceive('getPublicUrl')->andReturn('https://example.test/notes.pdf');

        $instance = Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])->instance();

        $result = $instance->finalizeReferenceFileUpload([
            'type' => 'PDF',
            'temp_key' => $tempKey,
            'title' => 'notes.pdf',
        ], app(FinalExamService::class), app(ExamReferenceFileService::class), app(AssessmentAttemptService::class));

        $this->assertArrayHasKey('file', $result);
        $this->assertSame('notes.pdf', $result['file']['title']);

        $this->assertDatabaseHas('exam_reference_files', [
            'assessment_id' => $openBookAssessment->id,
            'user_id' => $this->student->id,
            'title' => 'notes.pdf',
        ]);
    }

    public function test_delete_reference_file_only_removes_the_students_own_file(): void
    {
        $openBookAssessment = $this->makeOpenBookAssessment();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $otherStudent = User::factory()->forSchool($this->school)->create();
        $otherFile = ExamReferenceFile::factory()->for($openBookAssessment)->for($otherStudent)->create();
        $ownFile = ExamReferenceFile::factory()->for($openBookAssessment)->for($this->student)->create();

        $r2Mock = $this->mock(R2StorageService::class);
        $r2Mock->shouldReceive('delete')->once();
        $r2Mock->shouldReceive('getPublicUrl')->andReturn('https://example.test/file');

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])
            ->call('deleteReferenceFile', $ownFile->id)
            ->call('deleteReferenceFile', $otherFile->id);

        $this->assertDatabaseMissing('exam_reference_files', ['id' => $ownFile->id]);
        $this->assertDatabaseHas('exam_reference_files', ['id' => $otherFile->id]);
    }

    public function test_reference_file_actions_are_blocked_while_attempt_is_in_progress(): void
    {
        $openBookAssessment = $this->makeOpenBookAssessment();

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        AssessmentAttempt::factory()->for($openBookAssessment)->create([
            'user_id' => $this->student->id,
            'attempt_number' => 1,
            'submitted_at' => null,
        ]);

        $ownFile = ExamReferenceFile::factory()->for($openBookAssessment)->for($this->student)->create();

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])
            ->call('generateReferenceFileUploadUrl', 'notes.pdf', 'PDF')
            ->assertStatus(403);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])
            ->call('finalizeReferenceFileUpload', ['type' => 'PDF', 'temp_key' => 'foo', 'title' => 'notes.pdf'])
            ->assertStatus(403);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])
            ->call('deleteReferenceFile', $ownFile->id)
            ->assertStatus(403);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $openBookAssessment])
            ->call('deleteAllReferenceFiles')
            ->assertStatus(403);

        $this->assertDatabaseHas('exam_reference_files', ['id' => $ownFile->id]);
    }
}
