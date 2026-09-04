<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentPersonalGrade;
use App\Livewire\Courses\AssessmentPersonalShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentPersonalGradeTest extends TestCase
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

    public function test_teacher_grade_creates_score_and_student_sees_it(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $q1 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $q2 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 35]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentPersonalGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->set("gradeQuestionScores.{$q1->id}", '50')
            ->set("gradeQuestionScores.{$q2->id}", '35')
            ->set('gradeFeedback', 'Well done')
            ->call('submitGrade')
            ->assertRedirect(route('assessments.personal.show', $this->assessment));

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

    public function test_missing_score_shows_validation_error(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentPersonalGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->call('submitGrade')
            ->assertHasErrors();
    }

    public function test_without_attempt_returns_404(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentPersonalGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertStatus(404);
    }

    public function test_without_permission_forbidden(): void
    {
        AssessmentAttempt::factory()->for($this->assessment)->create(['user_id' => $this->student->id]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentPersonalGrade::class, ['assessment' => $this->assessment, 'student' => $this->student])
            ->assertStatus(403);
    }
}
