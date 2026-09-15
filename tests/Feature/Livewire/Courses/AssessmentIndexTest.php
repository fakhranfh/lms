<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
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
use App\Services\R2StorageService;
use Livewire\Livewire;
use Mockery;
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
            ->assertSee('Personal Assignment')
            ->assertSee('Team Assignment')
            ->assertSee('Quiz')
            ->assertSee('Final Exam');

        $this->actingAs($this->student);
        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertDontSee(route('assessments.create', [$this->course, 'personal']));
    }

    public function test_student_does_not_see_draft_personal_or_team_assignments(): void
    {
        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'title' => 'Draft Personal Assignment',
            'status' => AssessmentStatus::Draft,
        ]);
        Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
            'title' => 'Draft Team Assignment',
            'status' => AssessmentStatus::Draft,
        ]);
        Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'title' => 'Published Personal Assignment',
            'status' => AssessmentStatus::Published,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertDontSee('Draft Personal Assignment')
            ->assertDontSee('Draft Team Assignment')
            ->assertSee('Published Personal Assignment');
    }

    public function test_teacher_sees_draft_personal_and_team_assignments(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'title' => 'Draft Personal Assignment',
            'status' => AssessmentStatus::Draft,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Draft Personal Assignment');
    }

    public function test_teacher_can_publish_draft_personal_assignment(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'status' => AssessmentStatus::Draft,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('publishAssessment', $assessment->id);

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => AssessmentStatus::Published->value,
        ]);
    }

    public function test_teacher_can_unpublish_published_team_assignment(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
            'status' => AssessmentStatus::Published,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('unpublishAssessment', $assessment->id);

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => AssessmentStatus::Draft->value,
        ]);
    }

    public function test_publish_requires_assessment_edit_permission(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'status' => AssessmentStatus::Draft,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('publishAssessment', $assessment->id)
            ->assertStatus(403);
    }

    public function test_teacher_can_publish_draft_final_exam(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
            'status' => AssessmentStatus::Draft,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('publishAssessment', $assessment->id);

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => AssessmentStatus::Published->value,
        ]);
    }

    public function test_publish_ignored_for_non_assignment_types(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::Attendance,
            'status' => AssessmentStatus::Draft,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('publishAssessment', $assessment->id)
            ->assertSee('Only personal assignments, team assignments, quizzes, and final exams');

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => AssessmentStatus::Draft->value,
        ]);
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

    public function test_bulk_delete_removes_selected_assessments(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.delete']);
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);
        $second = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('deleteSelected', [$first->id, $second->id]);

        $this->assertDatabaseMissing('assessments', ['id' => $first->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $second->id]);
    }

    public function test_bulk_delete_skips_protected_assessments_and_reports_error(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.delete']);
        $this->actingAs($this->teacher);

        $deletable = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);
        $attendance = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::Attendance,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('deleteSelected', [$deletable->id, $attendance->id])
            ->assertSee('could not be deleted');

        $this->assertDatabaseMissing('assessments', ['id' => $deletable->id]);
        $this->assertDatabaseHas('assessments', ['id' => $attendance->id]);
    }

    public function test_generate_personal_assignments_creates_requested_count(): void
    {
        $r2Mock = Mockery::mock(R2StorageService::class);
        $r2Mock->shouldReceive('schoolPrefix')->andReturn('');
        $r2Mock->shouldReceive('uploadRawContent')->once()->andReturn('https://example.test/dummy.pdf');
        $this->app->instance(R2StorageService::class, $r2Mock);

        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '3')
            ->call('generatePersonalAssignments');

        $assessments = Assessment::query()
            ->where('course_id', $this->course->id)
            ->where('type', AssessmentType::TheoryPersonalAssignment)
            ->with('questions')
            ->get();

        $this->assertCount(3, $assessments);

        foreach ($assessments as $assessment) {
            $this->assertCount(3, $assessment->questions);
            $this->assertStringNotContainsString('lorem', strtolower($assessment->questions->first()->description));

            foreach ($assessment->questions as $question) {
                $this->assertNotEmpty($question->files);
            }
        }
    }

    public function test_generate_personal_assignments_validates_count(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '0')
            ->call('generatePersonalAssignments')
            ->assertHasErrors(['generateCount']);

        $this->assertDatabaseMissing('assessments', ['course_id' => $this->course->id]);
    }

    public function test_generate_team_assignments_creates_requested_count_with_groups(): void
    {
        $r2Mock = Mockery::mock(R2StorageService::class);
        $r2Mock->shouldReceive('schoolPrefix')->andReturn('');
        $r2Mock->shouldReceive('uploadRawContent')->once()->andReturn('https://example.test/dummy.pdf');
        $this->app->instance(R2StorageService::class, $r2Mock);

        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '2')
            ->call('generateTeamAssignments');

        $assessments = Assessment::query()
            ->where('course_id', $this->course->id)
            ->where('type', AssessmentType::TheoryTeamAssignment)
            ->with('questions')
            ->get();

        $this->assertCount(2, $assessments);

        foreach ($assessments as $assessment) {
            $this->assertSame(AssessmentAssignedTo::Group, $assessment->assigned_to);
            $this->assertCount(3, $assessment->questions);

            foreach ($assessment->questions as $question) {
                $this->assertNotEmpty($question->files);
            }
        }

        $this->assertDatabaseCount('groups', 2);
        $this->assertDatabaseHas('group_members', ['user_id' => $this->student->id]);
    }

    public function test_generate_team_assignments_validates_count(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '0')
            ->call('generateTeamAssignments')
            ->assertHasErrors(['generateCount']);

        $this->assertDatabaseMissing('assessments', ['course_id' => $this->course->id]);
    }

    public function test_generate_team_assignments_requires_enrolled_students(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '2')
            ->call('generateTeamAssignments')
            ->assertSet('errorMessage', 'This course has no enrolled students to form groups with.');

        $this->assertDatabaseMissing('assessments', ['course_id' => $this->course->id]);
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
            ->assertSee('Virtual Class')
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

    public function test_graded_assignment_with_zero_score_shows_graded_not_submitted(): void
    {
        $this->student->givePermissionTo('assessment.view');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
        ]);

        $attempt = AssessmentAttempt::factory()->for($assessment)->create(['user_id' => $this->student->id]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 0]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('Graded')
            ->assertDontSee('Submitted');
    }

    public function test_move_assessment_up_swaps_order_with_previous_sibling(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 1,
        ]);
        $second = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 2,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('moveAssessment', $second->id, 'up');

        $this->assertSame(2, $first->fresh()->order);
        $this->assertSame(1, $second->fresh()->order);
    }

    public function test_move_assessment_up_is_noop_when_already_first(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 1,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('moveAssessment', $first->id, 'up');

        $this->assertSame(1, $first->fresh()->order);
    }

    public function test_move_assessment_requires_permission(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 1,
        ]);
        $second = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 2,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('moveAssessment', $second->id, 'up')
            ->assertStatus(403);

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
    }

    public function test_reorder_assessments_persists_dragged_order(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.edit']);
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 1,
        ]);
        $second = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 2,
        ]);
        $third = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 3,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('reorderAssessments', AssessmentType::TheoryPersonalAssignment->value, [$third->id, $first->id, $second->id]);

        $this->assertSame(1, $third->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
        $this->assertSame(3, $second->fresh()->order);
    }

    public function test_reorder_assessments_requires_permission(): void
    {
        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        $first = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 1,
        ]);
        $second = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryPersonalAssignment,
            'order' => 2,
        ]);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->call('reorderAssessments', AssessmentType::TheoryPersonalAssignment->value, [$second->id, $first->id])
            ->assertStatus(403);

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
    }

    public function test_final_exam_shows_in_progress_when_attempt_not_yet_submitted(): void
    {
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
        $this->student->givePermissionTo('assessment.view');

        $assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        $attempt = AssessmentAttempt::factory()->for($assessment)->create([
            'user_id' => $this->student->id,
            'submitted_at' => null,
        ]);
        ProctorSession::factory()->for($attempt, 'attempt')->create(['reviewed_at' => null]);

        $this->actingAs($this->student);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->assertSee('In Progress')
            ->assertDontSee('Submitted')
            ->assertDontSee('Pending Review');
    }

    public function test_generate_final_exam_creates_requested_count_with_mc_and_essay_questions_for_open_and_closed_book(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        foreach ([FinalExamType::OpenBook, FinalExamType::ClosedBook] as $examType) {
            Livewire::test(AssessmentIndex::class, ['course' => $this->course])
                ->call('loadAssessments')
                ->set('generateCount', '2')
                ->call('generateFinalExam', $examType->value);

            $assessments = Assessment::query()
                ->where('course_id', $this->course->id)
                ->where('type', AssessmentType::TheoryFinalExam)
                ->whereHas('finalExam', fn ($query) => $query->where('exam_type', $examType))
                ->with('questions.options')
                ->get();

            $this->assertCount(2, $assessments);

            foreach ($assessments as $assessment) {
                $this->assertCount(20, $assessment->questions);

                $mcQuestions = $assessment->questions->where('question_type', AssessmentQuestionType::MultipleChoice);
                $essayQuestions = $assessment->questions->where('question_type', AssessmentQuestionType::Essay);

                $this->assertCount(15, $mcQuestions);
                $this->assertCount(5, $essayQuestions);

                foreach ($mcQuestions as $mcQuestion) {
                    $this->assertSame(0.0, (float) $mcQuestion->points);
                    $this->assertGreaterThanOrEqual(2, $mcQuestion->options->count());
                    $this->assertSame(1, $mcQuestion->options->where('is_correct', true)->count());
                }

                foreach ($essayQuestions as $essayQuestion) {
                    $this->assertGreaterThan(0, (float) $essayQuestion->points);
                }

                $this->assertSame($examType, $assessment->finalExam->exam_type);
            }
        }
    }

    public function test_generate_final_exam_creates_single_essay_question_for_take_home(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '2')
            ->call('generateFinalExam', FinalExamType::TakeHome->value);

        $assessments = Assessment::query()
            ->where('course_id', $this->course->id)
            ->where('type', AssessmentType::TheoryFinalExam)
            ->whereHas('finalExam', fn ($query) => $query->where('exam_type', FinalExamType::TakeHome))
            ->with('questions')
            ->get();

        $this->assertCount(2, $assessments);

        foreach ($assessments as $assessment) {
            $this->assertCount(1, $assessment->questions);
            $this->assertSame(AssessmentQuestionType::Essay, $assessment->questions->first()->question_type);
            $this->assertGreaterThan(0, (float) $assessment->questions->first()->points);
            $this->assertSame(FinalExamType::TakeHome, $assessment->finalExam->exam_type);
        }
    }

    public function test_generate_final_exam_validates_count(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '0')
            ->call('generateFinalExam', FinalExamType::ClosedBook->value)
            ->assertHasErrors(['generateCount']);

        $this->assertDatabaseMissing('assessments', [
            'course_id' => $this->course->id,
            'type' => AssessmentType::TheoryFinalExam->value,
        ]);
    }

    public function test_generate_all_final_exam_types_creates_one_of_each_type(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.create']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentIndex::class, ['course' => $this->course])
            ->call('loadAssessments')
            ->set('generateCount', '1')
            ->call('generateAllFinalExamTypes');

        $assessments = Assessment::query()
            ->where('course_id', $this->course->id)
            ->where('type', AssessmentType::TheoryFinalExam)
            ->with('finalExam', 'questions')
            ->get();

        $this->assertCount(3, $assessments);

        $examTypes = $assessments->pluck('finalExam.exam_type')->all();
        $this->assertEqualsCanonicalizing(
            [FinalExamType::OpenBook, FinalExamType::ClosedBook, FinalExamType::TakeHome],
            $examTypes
        );

        $takeHome = $assessments->first(fn ($assessment) => $assessment->finalExam->exam_type === FinalExamType::TakeHome);
        $this->assertCount(1, $takeHome->questions);

        $openBook = $assessments->first(fn ($assessment) => $assessment->finalExam->exam_type === FinalExamType::OpenBook);
        $this->assertCount(20, $openBook->questions);
    }
}
