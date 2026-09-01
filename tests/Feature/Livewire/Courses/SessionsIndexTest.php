<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\SessionsIndex;
use App\Models\Course;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class SessionsIndexTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $this->actingAs($this->teacher);
    }

    public function test_index_component_renders_with_sessions(): void
    {
        $this->teacher->givePermissionTo('sessions.view');

        Session::factory()->for($this->course)->create(['title' => 'Session 1']);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->assertStatus(200)
            ->call('loadSessions')
            ->assertSee('Session 1');
    }

    public function test_teacher_can_delete_a_session(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.delete']);

        $session = Session::factory()->for($this->course)->create();

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('confirmDelete', $session->id)
            ->assertSet('successMessage', 'Session deleted successfully.');

        $this->assertDatabaseMissing('course_sessions', ['id' => $session->id]);
    }

    public function test_teacher_can_bulk_delete_sessions(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.delete']);

        $sessions = Session::factory()->for($this->course)->count(3)->create();
        $keep = Session::factory()->for($this->course)->create();

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->set('selectedSessionIds', $sessions->pluck('id')->all())
            ->call('bulkDelete')
            ->assertSet('successMessage', 'Selected sessions deleted successfully.');

        foreach ($sessions as $session) {
            $this->assertDatabaseMissing('course_sessions', ['id' => $session->id]);
        }
        $this->assertDatabaseHas('course_sessions', ['id' => $keep->id]);
    }

    public function test_teacher_can_delete_all_sessions(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.delete']);

        Session::factory()->for($this->course)->count(3)->create();

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('deleteAll')
            ->assertSet('successMessage', 'All sessions deleted successfully.');

        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_teacher_can_reorder_sessions(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.edit']);

        $first = Session::factory()->for($this->course)->create(['order' => 1]);
        $second = Session::factory()->for($this->course)->create(['order' => 2]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('reorderSessions', [$second->id, $first->id]);

        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
    }

    public function test_teacher_can_move_session_up_and_down(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.edit']);

        $first = Session::factory()->for($this->course)->create(['order' => 1]);
        $second = Session::factory()->for($this->course)->create(['order' => 2]);
        $third = Session::factory()->for($this->course)->create(['order' => 3]);

        $component = Livewire::test(SessionsIndex::class, ['course' => $this->course]);

        $component->call('moveSessionDown', $first->id);
        $this->assertSame(2, $first->fresh()->order);
        $this->assertSame(1, $second->fresh()->order);

        $component->call('moveSessionUp', $third->id);
        $this->assertSame(2, $third->fresh()->order);
        $this->assertSame(3, $first->fresh()->order);
    }

    public function test_move_session_up_is_a_noop_at_the_boundary(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.edit']);

        $first = Session::factory()->for($this->course)->create(['order' => 1]);
        $second = Session::factory()->for($this->course)->create(['order' => 2]);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->call('moveSessionUp', $first->id);

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
    }

    public function test_dev_generate_sessions_creates_dummy_sessions(): void
    {
        $this->teacher->givePermissionTo(['sessions.view', 'sessions.create']);

        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->set('generateCount', 4)
            ->call('devGenerateSessions')
            ->assertSet('successMessage', '4 sessions generated.');

        $this->assertDatabaseCount('course_sessions', 4);
    }

    public function test_user_cannot_access_index_without_permission(): void
    {
        Livewire::test(SessionsIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_user_cannot_view_sessions_of_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo('sessions.view');

        Livewire::test(SessionsIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }
}
