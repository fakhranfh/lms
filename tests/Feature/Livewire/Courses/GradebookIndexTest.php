<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Livewire\Courses\GradebookIndex;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Forum;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class GradebookIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        app()->detectEnvironment(fn () => 'local');

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));
        $this->course = Course::factory()->for($this->school)->create();

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_user_without_permission_cannot_view_gradebook(): void
    {
        $this->actingAs($this->student);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_student_sees_own_final_score_card(): void
    {
        $this->student->givePermissionTo('gradebook.view');
        $this->actingAs($this->student);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => 'theory_personal_assignment', 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->for($this->student)->create();
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 80, 'graded_at' => now()]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Final Score')
            ->assertSee('THEORY: Personal Assignment');
    }

    public function test_teacher_sees_student_grid_with_link_to_detail_page(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee($this->student->name)
            ->assertSee(route('gradebook.show', [$this->course, $this->student]), false);
    }

    public function test_teacher_student_grid_is_paginated(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $extraStudents = User::factory()->forSchool($this->school)->count(15)->create();
        foreach ($extraStudents as $extraStudent) {
            CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $extraStudent->id]);
        }

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set('perPage', 12)
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->count() === 12 && $studentRows->total() === 16)
            ->call('gotoPage', 2)
            ->assertViewHas('studentRows', fn ($studentRows) => $studentRows->count() === 4);
    }

    public function test_randomize_scores_is_forbidden_outside_local_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores')
            ->assertStatus(403);

        app()->detectEnvironment(fn () => 'testing');
    }

    public function test_randomize_scores_grades_a_personal_assignment(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::TheoryPersonalAssignment, 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores');

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)->where('user_id', $this->student->id)->first();

        $this->assertNotNull($attempt);
        $this->assertNotNull($attempt->score);
        $this->assertGreaterThanOrEqual(40, $attempt->score->score);
        $this->assertLessThanOrEqual(100, $attempt->score->score);
    }

    public function test_randomize_scores_grades_a_team_assignment(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::TheoryTeamAssignment, 'weight' => 15]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 50]);
        $group = Group::factory()->for($this->course)->create();
        GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores');

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)->where('group_id', $group->id)->first();

        $this->assertNotNull($attempt);
        $this->assertNotNull($attempt->score);
    }

    public function test_randomize_scores_fills_attendance_records(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Assessment::factory()->for($this->course)->create(['type' => AssessmentType::Attendance, 'weight' => 10]);
        $session = Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::VirtualClass]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores');

        $this->assertTrue(Attendance::where('session_id', $session->id)->where('user_id', $this->student->id)->exists());
    }

    public function test_randomize_scores_fills_forum_discussion_posts(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::ForumDiscussion, 'weight' => 10]);
        $session = Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::Online]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores');

        // Forum posts are randomized per session (0 to required+1 posts), so the
        // count itself is not deterministic; what must always hold is that a
        // Forum was provisioned for the session and the derived attempt/score
        // pipeline ran for the student.
        $forum = Forum::where('session_id', $session->id)->first();
        $this->assertNotNull($forum);

        $this->assertTrue(
            AssessmentAttempt::where('assessment_id', $assessment->id)->where('user_id', $this->student->id)->exists()
        );
    }

    public function test_reset_scores_clears_all_gradable_data(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $assessment = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::TheoryPersonalAssignment, 'weight' => 20]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);
        Assessment::factory()->for($this->course)->create(['type' => AssessmentType::Attendance, 'weight' => 10]);
        Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::VirtualClass]);
        Assessment::factory()->for($this->course)->create(['type' => AssessmentType::ForumDiscussion, 'weight' => 10]);
        Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::Online]);

        $component = Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores');

        $this->assertDatabaseCount('assessment_attempts', 3);

        $component->call('resetScores');

        $this->assertDatabaseCount('assessment_attempts', 0);
        $this->assertDatabaseCount('assessment_scores', 0);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('forum_threads', 0);
    }

    public function test_randomize_scores_produces_every_letter_grade_with_enough_students(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        // 4 more students so there are 5 total, one per A-E grade band.
        $extraStudents = User::factory()->forSchool($this->school)->count(4)->create();
        foreach ($extraStudents as $extraStudent) {
            CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $extraStudent->id]);
        }

        $assessment = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::TheoryPersonalAssignment, 'weight' => 100]);
        AssessmentQuestion::factory()->for($assessment)->create(['points' => 100]);

        Livewire::test(GradebookIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('randomizeScores');

        $grades = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->with('score')
            ->get()
            ->map(fn (AssessmentAttempt $attempt) => match (true) {
                $attempt->score->score >= 90 => 'A',
                $attempt->score->score >= 80 => 'B',
                $attempt->score->score >= 70 => 'C',
                $attempt->score->score >= 60 => 'D',
                default => 'E',
            })
            ->unique()
            ->sort()
            ->values();

        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $grades->all());
    }
}
