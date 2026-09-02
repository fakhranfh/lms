<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\RoleName;
use App\Livewire\Courses\SyllabusIndex;
use App\Models\Course;
use App\Models\Role;
use App\Models\School;
use App\Models\Syllabus;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class SyllabusIndexStudentTest extends TestCase
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

        $this->actingAs($this->student);
    }

    public function test_user_cannot_access_index_without_permission(): void
    {
        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_shows_not_yet_published_message_when_no_syllabus(): void
    {
        $this->student->givePermissionTo('syllabus.view');

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertSee('not been published yet');
    }

    public function test_read_only_render_has_no_edit_affordances(): void
    {
        $this->student->givePermissionTo('syllabus.view');

        Syllabus::factory()->for($this->course)->create(['course_description' => 'Student visible description']);

        Livewire::test(SyllabusIndex::class, ['course' => $this->course])
            ->call('loadSyllabus')
            ->assertSee('Student visible description')
            ->assertDontSee('Edit Syllabus')
            ->assertDontSee('Create Syllabus');
    }

    public function test_student_cannot_enter_syllabus_edit_mode(): void
    {
        $this->student->givePermissionTo('syllabus.view');

        Livewire::test(SyllabusIndex::class, ['course' => $this->course, 'startInEditMode' => true])
            ->assertStatus(403);
    }
}
