<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentTeamGrade;
use App\Livewire\Courses\AssessmentTeamShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentTeamGradeTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $studentOne;

    private User $studentTwo;

    private Course $course;

    private Assessment $assessment;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->studentOne = User::factory()->forSchool($this->school)->create();
        $this->studentTwo = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->studentOne->assignRole($studentRole);
        $this->studentTwo->assignRole($studentRole);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->studentOne->id]);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->studentTwo->id]);

        $this->group = Group::factory()->for($this->course)->create();
        GroupMember::factory()->for($this->group)->create(['user_id' => $this->studentOne->id]);
        GroupMember::factory()->for($this->group)->create(['user_id' => $this->studentTwo->id]);

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
    }

    public function test_teacher_grading_group_updates_shared_attempt_visible_to_all_members(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $q1 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        $q2 = AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 40]);

        $attempt = AssessmentAttempt::factory()->for($this->assessment)->create([
            'group_id' => $this->group->id,
            'user_id' => null,
            'submitted_by' => $this->studentOne->id,
        ]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentTeamGrade::class, ['assessment' => $this->assessment, 'group' => $this->group])
            ->set("gradeQuestionScores.{$q1->id}", '50')
            ->set("gradeQuestionScores.{$q2->id}", '40')
            ->call('submitGrade')
            ->assertRedirect(route('assessments.team.show', $this->assessment));

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_attempt_id' => $attempt->id,
            'score' => 90,
        ]);

        foreach ([$this->studentOne, $this->studentTwo] as $member) {
            $member->givePermissionTo(['assessment.view', 'assessment.submit']);
            $this->actingAs($member);
            Livewire::test(AssessmentTeamShow::class, ['assessment' => $this->assessment])
                ->assertSee('90');
        }
    }

    public function test_missing_score_shows_validation_error(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        AssessmentQuestion::factory()->for($this->assessment)->create(['points' => 50]);
        AssessmentAttempt::factory()->for($this->assessment)->create([
            'group_id' => $this->group->id,
            'user_id' => null,
            'submitted_by' => $this->studentOne->id,
        ]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentTeamGrade::class, ['assessment' => $this->assessment, 'group' => $this->group])
            ->call('submitGrade')
            ->assertHasErrors();
    }

    public function test_without_attempt_returns_404(): void
    {
        $this->teacher->givePermissionTo(['assessment.view', 'assessment.grade']);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentTeamGrade::class, ['assessment' => $this->assessment, 'group' => $this->group])
            ->assertStatus(404);
    }

    public function test_without_permission_forbidden(): void
    {
        AssessmentAttempt::factory()->for($this->assessment)->create([
            'group_id' => $this->group->id,
            'user_id' => null,
            'submitted_by' => $this->studentOne->id,
        ]);

        $this->actingAs($this->teacher);
        Livewire::test(AssessmentTeamGrade::class, ['assessment' => $this->assessment, 'group' => $this->group])
            ->assertStatus(403);
    }
}
