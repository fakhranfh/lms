<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AttendanceStatus;
use App\Livewire\Courses\AttendanceIndex;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
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
        $this->session = Session::factory()->create(['course_id' => $this->course->id]);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
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
            ->assertSee($this->session->title);
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
}
