<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorSnapshotType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentFinalExamShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
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
            'allow_local_files' => false,
            'allow_internet' => false,
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

    public function test_exam_type_and_allow_flags_render(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentFinalExamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Closed Book')
            ->assertSee('Not allowed');
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

    public function test_teacher_grade_creates_score_but_student_does_not_see_score_or_feedback(): void
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
            ->assertDontSee('85')
            ->assertDontSee('Well done')
            ->assertSee('awaiting the teacher');
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
}
