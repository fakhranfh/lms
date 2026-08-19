<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Enums\FinalExamType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentIndex;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\ProctorSession;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);
    }

    public function test_user_cannot_access_index_without_permission(): void
    {
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_user_cannot_view_assessments_of_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }

    public function test_lists_assessments_grouped_by_type(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'title' => 'Essay One',
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Personal Assignment')
            ->assertSee('Essay One');
    }

    public function test_teacher_sees_create_button_student_does_not(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->student->givePermissionTo('assessment.view');

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Create Assessment');

        $this->actingAs($this->student);
        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertDontSee('Create Assessment');
    }

    public function test_delete_blocked_when_attempts_exist(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.delete']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);
        AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $this->student->id]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('deleteAssessment', $assessment->id)
            ->assertSee('cannot be deleted');

        $this->assertDatabaseHas('assessments', ['id' => $assessment->id]);
    }

    public function test_delete_blocked_for_auto_provisioned_attendance_assessment(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.delete']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::Attendance,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('deleteAssessment', $assessment->id)
            ->assertSee('auto-provisioned');

        $this->assertDatabaseHas('assessments', ['id' => $assessment->id]);
    }

    public function test_delete_succeeds_when_no_attempts(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.delete']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('deleteAssessment', $assessment->id);

        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
    }

    public function test_student_sees_per_session_attendance_table(): void
    {
        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $session = Session::factory()->create([
            'course_id' => $this->course->id,
            'title' => 'Session 1 - Virtual Class - CL',
            'delivery_mode' => DeliveryMode::VirtualClass,
        ]);

        Assessment::factory()->for($this->course)->create(['type' => AssessmentType::Attendance]);

        Attendance::factory()->create([
            'session_id' => $session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Present,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('toggleSection', AssessmentType::Attendance->value)
            ->assertSee('Session 1')
            ->assertSee('Session 1 - Virtual Class - CL')
            ->assertSee('Completed')
            ->assertSee('100 pts');
    }

    public function test_quiz_row_links_to_quiz_show(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryQuiz,
            'title' => 'Chapter Quiz',
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Chapter Quiz')
            ->assertSee(route('assessments.quiz.show', $assessment), false);
    }

    public function test_student_quiz_status_not_started_when_no_attempts(): void
    {
        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryQuiz,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Not Started');
    }

    public function test_proctored_final_exam_shows_pending_review_until_proctor_session_reviewed(): void
    {
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
        $this->student->givePermissionTo('assessment.view');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);

        $attempt = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $this->student->id]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 90]);
        $session = ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => null]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Pending Review')
            ->assertDontSee('Graded');

        $session->update(['reviewed_at' => now(), 'reviewed_by' => $this->teacher->id]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Graded')
            ->assertDontSee('Pending Review');
    }

    public function test_graded_final_exam_shows_score(): void
    {
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
        $this->student->givePermissionTo('assessment.view');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);

        $attempt = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $this->student->id]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 87.5]);
        ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => now(), 'reviewed_by' => $this->teacher->id]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('87.5');
    }
}
