<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Livewire\Courses\AttendanceIndex;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use App\Models\VideoConference;
use App\Models\VideoConferenceParticipation;
use App\Services\AttendanceDraftService;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
        $this->session = Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::Offline]);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);
    }

    public function test_user_without_permission_cannot_view_attendance(): void
    {
        $this->actingAs($this->student);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_student_sees_read_only_attendance_summary(): void
    {
        $this->student->givePermissionTo('attendance.view');
        $this->actingAs($this->student);

        Attendance::factory()->create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Present,
        ]);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Attendance')
            ->assertSee('Session 1');
    }

    public function test_teacher_can_record_manual_attendance_override(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('recordAttendance', $this->session->id, $this->student->id, 'present', 'On time')
            ->assertSet('errorMessage', null);

        $this->assertDatabaseHas('attendances', [
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Present->value,
            'recorded_by' => $this->teacher->id,
            'notes' => 'On time',
        ]);
    }

    public function test_student_cannot_record_attendance(): void
    {
        $this->student->givePermissionTo('attendance.view');
        $this->actingAs($this->student);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('recordAttendance', $this->session->id, $this->student->id, 'present', '')
            ->assertStatus(403);
    }

    public function test_student_sees_attendance_requirement_description_for_each_session(): void
    {
        $this->student->givePermissionTo('attendance.view');
        $this->actingAs($this->student);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Attendance Requirement')
            ->assertSee('Teacher mark');
    }

    public function test_online_sessions_are_excluded_from_the_attendance_table(): void
    {
        Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::Online]);

        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        $component = Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData');

        expect($component->viewData('sessions')->pluck('delivery_mode'))
            ->each->not->toBe(DeliveryMode::Online);
    }

    public function test_only_virtual_class_and_offline_sessions_appear_as_teacher_tabs(): void
    {
        Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
        Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::Online]);

        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        $component = Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData');

        $deliveryModes = $component->viewData('sessions')->pluck('delivery_mode');

        expect($deliveryModes)->toHaveCount(2);
        expect($deliveryModes)->each->not->toBe(DeliveryMode::Online);
    }

    public function test_session_query_string_preselects_the_session_tab(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        $otherSession = Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);

        Livewire::withQueryParams(['session' => $otherSession->id])
            ->test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSet('selectedSessionId', $otherSession->id);
    }

    public function test_teacher_sees_self_checkin_datetime_for_virtual_class_session(): void
    {
        $virtualSession = Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::VirtualClass]);
        $conference = VideoConference::factory()->create(['session_id' => $virtualSession->id]);

        VideoConferenceParticipation::factory()->create([
            'video_conference_id' => $conference->id,
            'user_id' => $this->student->id,
            'joined_at' => now()->subMinutes(5),
            'left_at' => null,
        ]);

        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::withQueryParams(['session' => $virtualSession->id])
            ->test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Present (self check-in)');
    }

    public function test_teacher_can_search_students_by_name(): void
    {
        $matching = User::factory()->forSchool($this->school)->create(['name' => 'Zoe Wildflower']);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $matching->id]);

        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set('studentSearch', 'wildflower')
            ->assertSee('Zoe Wildflower')
            ->assertDontSee($this->student->name);
    }

    public function test_searching_students_resets_pagination_to_first_page(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set('paginators.page', 3)
            ->set('studentSearch', 'anything')
            ->assertSet('paginators.page', 1);
    }

    public function test_student_table_paginates_beyond_the_first_page(): void
    {
        User::factory()->forSchool($this->school)->count(15)->create()->each(
            fn (User $user) => CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $user->id])
        );

        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        $component = Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData');

        expect($component->viewData('studentRows')->count())->toBe(10);
        expect($component->viewData('studentRows')->total())->toBe(16);
    }

    public function test_notes_field_is_only_relevant_when_status_is_excused(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('recordAttendance', $this->session->id, $this->student->id, 'excused', 'Sick leave')
            ->assertSet('errorMessage', null);

        $this->assertDatabaseHas('attendances', [
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Excused->value,
            'notes' => 'Sick leave',
        ]);
    }

    public function test_add_notes_button_and_editor_are_wired_to_the_students_draft_notes(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        // Visibility of the "Add Notes" button/modal is driven client-side by
        // Alpine (x-show="status === 'excused'"), not a server re-render, so
        // the button and its rich-text-editor are always in the markup —
        // assert they're wired to this student's draft rather than asserting
        // presence/absence.
        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('Add Notes')
            ->assertSee("drafts.{$this->student->id}.notes", false);
    }

    public function test_marking_attendance_persists_a_draft_to_redis_surviving_a_fresh_component_instance(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set("drafts.{$this->student->id}.status", 'late');

        $this->assertDatabaseMissing('attendances', [
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
        ]);

        // A brand new component instance (simulating a page refresh) should
        // restore the draft from Redis instead of defaulting to "absent".
        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSet("drafts.{$this->student->id}.status", 'late');
    }

    public function test_save_all_persists_every_drafted_change_and_clears_the_draft(): void
    {
        $otherStudent = User::factory()->forSchool($this->school)->create();
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $otherStudent->id]);

        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set("drafts.{$this->student->id}.status", 'excused')
            ->set("drafts.{$this->student->id}.notes", 'Family emergency')
            ->set("drafts.{$otherStudent->id}.status", 'present')
            ->call('saveAllAttendance')
            ->assertSet('errorMessage', null);

        $this->assertDatabaseHas('attendances', [
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Excused->value,
            'notes' => 'Family emergency',
        ]);

        $this->assertDatabaseHas('attendances', [
            'session_id' => $this->session->id,
            'user_id' => $otherStudent->id,
            'status' => AttendanceStatus::Present->value,
        ]);

        expect(app(AttendanceDraftService::class)->all($this->session->id))->toBe([]);
    }

    public function test_recording_a_single_attendance_clears_that_users_redis_draft(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set("drafts.{$this->student->id}.status", 'late')
            ->call('recordAttendance', $this->session->id, $this->student->id, 'present', '');

        $this->assertDatabaseHas('attendances', [
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Present->value,
        ]);

        expect(app(AttendanceDraftService::class)->all($this->session->id))->toBe([]);
    }

    public function test_save_all_locks_the_session_so_it_cannot_be_edited_again(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set("drafts.{$this->student->id}.status", 'present')
            ->call('saveAllAttendance')
            ->assertSet('errorMessage', null);

        $this->assertTrue($this->session->fresh()->isAttendanceLocked());

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSee('has been saved and locked')
            ->assertSee('disabled', false);
    }

    public function test_save_all_is_rejected_once_the_session_is_already_locked(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);
        $this->session->update(['attendance_locked_at' => now()]);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->set("drafts.{$this->student->id}.status", 'present')
            ->call('saveAllAttendance')
            ->assertStatus(403);
    }

    public function test_record_attendance_is_rejected_once_the_session_is_already_locked(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);
        $this->session->update(['attendance_locked_at' => now()]);

        Livewire::test(AttendanceIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('recordAttendance', $this->session->id, $this->student->id, 'present', '')
            ->assertStatus(403);
    }
}
