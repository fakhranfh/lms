<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\MaterialType;
use App\Enums\RoleName;
use App\Livewire\Courses\SessionsIndex;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
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
}
