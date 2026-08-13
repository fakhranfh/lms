<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\AttendanceRequirementSettings;
use App\Models\AttendanceRequirement;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceRequirementSettingsTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
    }

    public function test_student_cannot_access_settings(): void
    {
        $this->student->givePermissionTo('attendance.view');
        $this->actingAs($this->student);

        Livewire::test(AttendanceRequirementSettings::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_teacher_can_add_move_and_delete_requirements(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        $component = Livewire::test(AttendanceRequirementSettings::class, ['course' => $this->course])
            ->set('requirementType', 'forum_completed')
            ->set('label', 'Forum Completed')
            ->call('addRequirement');

        $this->assertDatabaseHas('attendance_requirements', [
            'course_id' => $this->course->id,
            'label' => 'Forum Completed',
        ]);

        $requirement = AttendanceRequirement::where('course_id', $this->course->id)->first();

        $component->call('deleteRequirement', $requirement->id);

        $this->assertDatabaseMissing('attendance_requirements', ['id' => $requirement->id]);
    }

    public function test_teacher_can_save_minimal_attendance(): void
    {
        $this->teacher->givePermissionTo(['attendance.view', 'attendance.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(AttendanceRequirementSettings::class, ['course' => $this->course])
            ->set('minimalAttendance', '8')
            ->call('saveMinimalAttendance');

        $this->assertDatabaseHas('course_attendance_settings', [
            'course_id' => $this->course->id,
            'minimal_attendance' => 8,
        ]);
    }
}
