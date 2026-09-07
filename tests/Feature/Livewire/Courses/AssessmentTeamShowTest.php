<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentTeamShow;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentTeamShowTest extends TestCase
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

    public function test_student_without_group_sees_empty_state_and_cannot_submit(): void
    {
        $unassigned = User::factory()->forSchool($this->school)->create();
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $unassigned->assignRole($studentRole);
        $unassigned->givePermissionTo(['assessment.view', 'assessment.submit']);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $unassigned->id]);

        $this->actingAs($unassigned);

        Livewire::test(AssessmentTeamShow::class, ['assessment' => $this->assessment])
            ->assertSee('not yet assigned to a group')
            ->set('answerText', 'Trying anyway')
            ->call('submit')
            ->assertSee('not assigned to a group');

        $this->assertDatabaseMissing('assessment_attempts', ['assessment_id' => $this->assessment->id]);
    }

    public function test_any_group_member_can_submit_and_all_see_it(): void
    {
        $this->studentOne->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->studentTwo->givePermissionTo(['assessment.view', 'assessment.submit']);

        $this->actingAs($this->studentOne);
        Livewire::test(AssessmentTeamShow::class, ['assessment' => $this->assessment])
            ->set('answerText', 'Our group answer')
            ->call('submit');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'group_id' => $this->group->id,
            'submitted_by' => $this->studentOne->id,
        ]);

        $this->actingAs($this->studentTwo);
        Livewire::test(AssessmentTeamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Our group answer');
    }

    public function test_no_groups_shows_empty_state_for_teacher(): void
    {
        $emptyAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
        ]);
        $emptyCourse = Course::factory()->for($this->school)->create();
        $noGroupAssessment = Assessment::factory()->for($emptyCourse)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
        ]);

        $this->teacher->givePermissionTo('assessment.view');
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentTeamShow::class, ['assessment' => $noGroupAssessment])
            ->assertSee('No groups yet');
    }

    public function test_your_group_section_lists_each_member_name_and_avatar(): void
    {
        $this->studentOne->givePermissionTo(['assessment.view', 'assessment.submit']);

        $this->actingAs($this->studentOne);

        Livewire::test(AssessmentTeamShow::class, ['assessment' => $this->assessment])
            ->assertSee('Your Group')
            ->assertSee($this->studentOne->name)
            ->assertSee($this->studentTwo->name)
            ->assertSeeHtml('rounded-full');
    }

    public function test_shows_attempt_history_and_expired_badge(): void
    {
        $expiredAssessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryTeamAssignment,
            'end_date' => now()->subDay(),
        ]);
        AssessmentAttempt::factory()->for($expiredAssessment)->create([
            'group_id' => $this->group->id,
            'user_id' => null,
            'submitted_by' => $this->studentOne->id,
            'attempt_number' => 1,
        ]);

        $this->studentOne->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->studentOne);

        Livewire::test(AssessmentTeamShow::class, ['assessment' => $expiredAssessment])
            ->assertSee('Expired')
            ->assertSee('Answer Attempts')
            ->assertSee('Attempt 1');
    }

    public function test_student_cannot_submit_before_start_date(): void
    {
        $this->assessment->update(['start_date' => now()->addDay()]);

        $this->studentOne->givePermissionTo(['assessment.view', 'assessment.submit']);
        $this->actingAs($this->studentOne);

        Livewire::test(AssessmentTeamShow::class, ['assessment' => $this->assessment])
            ->assertSee('not open yet')
            ->set('answerText', 'Too early')
            ->call('submit')
            ->assertSee('not open yet');

        $this->assertDatabaseMissing('assessment_attempts', ['assessment_id' => $this->assessment->id]);
    }
}
