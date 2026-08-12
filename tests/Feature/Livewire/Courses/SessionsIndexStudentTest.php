<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\MaterialType;
use App\Enums\RoleName;
use App\Livewire\Courses\SessionsIndex;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MediaLibraryItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use App\Models\VideoConference;
use Livewire\Livewire;
use Tests\TestCase;

class SessionsIndexStudentTest extends TestCase
{
    private School $school;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);
        $this->student->givePermissionTo('sessions.view');

        $this->actingAs($this->student);
    }

    public function test_student_sees_tab_layout_with_active_session_detail(): void
    {
        $session = Session::factory()->for($this->course)->create([
            'title' => 'Session 1',
            'learning_outcome' => 'Understand the basics',
        ]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->assertStatus(200)
            ->call('loadSessions')
            ->assertSee('Session 1')
            ->assertSee('Understand the basics')
            ->assertSee('Learning Outcome');
    }

    public function test_student_sees_teacher_name_above_session_tabs(): void
    {
        $teacher = User::factory()->forSchool($this->school)->create(['name' => 'Jane Teacher']);
        CoursePerson::factory()->teacher()->for($this->course)->for($teacher)->create();

        Session::factory()->for($this->course)->create();

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->assertSee('Jane Teacher');
    }

    public function test_student_marking_material_completed_updates_progress_and_cannot_be_undone(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['type' => MaterialType::PDF]);
        $session->materials()->attach($material->id, ['order' => 1]);

        $component = Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('0%');

        $component->call('markMaterialCompleted', $material->id)
            ->assertSee('100%');

        $this->assertDatabaseHas('session_material_completions', [
            'session_id' => $session->id,
            'media_library_item_id' => $material->id,
            'user_id' => $this->student->id,
        ]);

        // Calling it again must not remove the completion (one-way).
        $component->call('markMaterialCompleted', $material->id)
            ->assertSee('100%');

        $this->assertDatabaseHas('session_material_completions', [
            'session_id' => $session->id,
            'media_library_item_id' => $material->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_video_conference_shows_only_for_virtual_class_sessions_and_tracks_opened_state(): void
    {
        $onlineSession = Session::factory()->for($this->course)->create([
            'delivery_mode' => DeliveryMode::VirtualClass,
            'date_start' => now()->subDay(),
            'date_end' => now()->addDay(),
        ]);
        $videoConference = VideoConference::factory()->for($onlineSession)->create(['title' => 'Main Meeting']);

        $offlineSession = Session::factory()->for($this->course)->create(['delivery_mode' => DeliveryMode::Offline]);
        VideoConference::factory()->for($offlineSession)->create(['title' => 'Should Not Show']);

        $component = Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $offlineSession->id)
            ->assertDontSee('Should Not Show');

        $component->call('selectSession', $onlineSession->id)
            ->assertSee('Main Meeting');

        $component->call('markVideoConferenceOpened', $videoConference->id);

        $this->assertDatabaseHas('video_conference_participations', [
            'video_conference_id' => $videoConference->id,
            'user_id' => $this->student->id,
        ]);

        // Calling it again must not create a duplicate participation row.
        $component->call('markVideoConferenceOpened', $videoConference->id);

        $this->assertDatabaseCount('video_conference_participations', 1);
    }

    public function test_video_conference_cannot_be_opened_outside_session_window(): void
    {
        $pastSession = Session::factory()->for($this->course)->create([
            'delivery_mode' => DeliveryMode::VirtualClass,
            'date_start' => now()->subWeeks(2),
            'date_end' => now()->subWeek(),
        ]);
        $videoConference = VideoConference::factory()->for($pastSession)->create();

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $pastSession->id)
            ->call('markVideoConferenceOpened', $videoConference->id)
            ->assertStatus(403);

        $this->assertDatabaseMissing('video_conference_participations', [
            'video_conference_id' => $videoConference->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_assessment_pill_shows_empty_state_when_session_has_no_assessment(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['type' => MaterialType::PDF]);
        $session->materials()->attach($material->id, ['order' => 1]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('No assessment yet.');
    }

    public function test_assessment_pill_shows_start_link_when_not_started(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $assessment = Assessment::factory()->for($this->course)->create([
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryPersonalAssignment,
            'assigned_to' => AssessmentAssignedTo::Individual,
            'title' => 'Reflection Essay',
        ]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('Reflection Essay')
            ->assertSee('Personal Assignment')
            ->assertSee('Not Started')
            ->assertSee(route('assessments.personal.show', $assessment), false);
    }

    public function test_assessment_pill_shows_graded_score(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $assessment = Assessment::factory()->for($this->course)->create([
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryPersonalAssignment,
            'assigned_to' => AssessmentAssignedTo::Individual,
        ]);
        $attempt = AssessmentAttempt::factory()->for($assessment)->create([
            'user_id' => $this->student->id,
            'submitted_by' => $this->student->id,
        ]);
        AssessmentScore::factory()->for($attempt, 'attempt')->create(['score' => 88]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('Graded')
            ->assertSee('88');
    }

    public function test_assessment_pill_reflects_team_assignment_via_group_membership(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $assessment = Assessment::factory()->for($this->course)->create([
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryTeamAssignment,
            'assigned_to' => AssessmentAssignedTo::Group,
        ]);
        $group = Group::factory()->for($this->course)->create();
        GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);
        AssessmentAttempt::factory()->for($assessment)->create([
            'user_id' => null,
            'group_id' => $group->id,
            'submitted_by' => $this->student->id,
        ]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('Team Assignment')
            ->assertSee('Submitted');
    }

    public function test_session_with_multiple_assessments_lists_each_one(): void
    {
        $session = Session::factory()->for($this->course)->create();
        Assessment::factory()->for($this->course)->create([
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryPersonalAssignment,
            'assigned_to' => AssessmentAssignedTo::Individual,
            'title' => 'Essay One',
        ]);
        Assessment::factory()->for($this->course)->create([
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryPersonalAssignment,
            'assigned_to' => AssessmentAssignedTo::Individual,
            'title' => 'Essay Two',
        ]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('Essay One')
            ->assertSee('Essay Two');
    }

    public function test_selecting_a_session_and_chip_persists_state_for_the_url(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['type' => MaterialType::PDF]);
        $session->materials()->attach($material->id, ['order' => 1]);

        $component = Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSet('activeSessionId', $session->id)
            ->assertSet('activeCategory', 'material')
            ->assertSet('activeMaterialId', null);

        $component->call('selectChip', 'assessment')
            ->assertSet('activeCategory', 'assessment')
            ->assertSet('activeMaterialId', null);

        $component->call('selectChip', 'material:'.$material->id)
            ->assertSet('activeCategory', 'material')
            ->assertSet('activeMaterialId', (string) $material->id);

        $component->call('selectChip', 'forum')
            ->assertSet('activeCategory', 'forum')
            ->assertSet('activeMaterialId', null);
    }

    public function test_submitted_assessment_counts_toward_learning_progress(): void
    {
        $session = Session::factory()->for($this->course)->create();
        $material = MediaLibraryItem::factory()->for($this->school)->create(['type' => MaterialType::PDF]);
        $session->materials()->attach($material->id, ['order' => 1]);
        $assessment = Assessment::factory()->for($this->course)->create([
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryPersonalAssignment,
            'assigned_to' => AssessmentAssignedTo::Individual,
        ]);

        // Neither the material nor the assessment is done yet: 0%.
        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('0%');

        // Assessment submitted (not yet graded) counts as done: 50%.
        AssessmentAttempt::factory()->for($assessment)->create([
            'user_id' => $this->student->id,
            'submitted_by' => $this->student->id,
        ]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->assertSee('50%');

        // Material also completed: 100%.
        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('loadSessions')
            ->call('selectSession', $session->id)
            ->call('markMaterialCompleted', $material->id)
            ->assertSee('100%');
    }
}
