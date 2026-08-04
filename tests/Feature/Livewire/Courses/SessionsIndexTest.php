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
