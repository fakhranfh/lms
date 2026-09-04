<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentPersonalShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentPersonalShowTest extends TestCase
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
            'type' => AssessmentType::TheoryPersonalAssignment,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
    }

    public function test_student_submit_creates_attempt_and_answer(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'My answer')
            ->call('submit');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
            'attempt_number' => 1,
        ]);
        $this->assertDatabaseHas('assessment_answers', ['answer_text' => 'My answer']);
    }

    public function test_student_cannot_submit_empty_answer(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->set('answerText', '<p><br></p>')
            ->call('submit')
            ->assertHasErrors(['answerText']);

        $this->assertDatabaseMissing('assessment_attempts', ['assessment_id' => $this->assessment->id]);
    }

    public function test_student_can_resubmit_before_grading_incrementing_attempt(): void
    {
        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        $component = Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'First try')
            ->call('submit');

        $component->set('answerText', 'Second try')->call('submit');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
            'attempt_number' => 2,
        ]);
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

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
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

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'Late answer')
            ->call('submit')
            ->assertSee('submission window');

        $this->assertDatabaseMissing('assessment_attempts', ['assessment_id' => $this->assessment->id]);
    }

    public function test_student_cannot_submit_before_start_date(): void
    {
        $this->assessment->update(['start_date' => now()->addDay()]);

        $this->student->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->student);

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->assertSee('not open yet')
            ->set('answerText', 'Too early')
            ->call('submit')
            ->assertSee('not open yet');

        $this->assertDatabaseMissing('assessment_attempts', ['assessment_id' => $this->assessment->id]);
    }

    public function test_teacher_sees_per_student_rows(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);
        $this->actingAs($this->teacher);

        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->assertSee($this->student->name);
    }

    public function test_teacher_grade_creates_score_and_student_sees_it(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $q1 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $q2 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 35]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
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

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->assertSee('85')
            ->assertSee('Well done');
    }

    public function test_cross_school_access_forbidden(): void
    {
        $otherSchool = School::factory()->create();
        $outsider = User::factory()->forSchool($otherSchool)->create();
        $outsider->givePermissionTo('assessment.view');

        $this->actingAs($outsider);
        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->assertStatus(404);
    }

    public function test_unenrolled_student_access_forbidden(): void
    {
        $unenrolledStudent = User::factory()->forSchool($this->school)->create();
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $unenrolledStudent->assignRole($studentRole);
        $unenrolledStudent->givePermissionTo(['assessment.view', 'assessment.submit']);

        $this->actingAs($unenrolledStudent);
        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $this->assessment])
            ->assertStatus(403);
    }

    public function test_wrong_type_returns_404(): void
    {
        $teamAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
        ]);

        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentPersonalShow::class, ['assessment' => $teamAssessment])
            ->assertStatus(404);
    }
}
