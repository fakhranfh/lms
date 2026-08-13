<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\AttendanceStatus;
use App\Livewire\Courses\AssessmentAttendanceShow;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentAttendanceShowTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Assessment $assessment;

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

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::Attendance,
            'weight' => 10,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function test_student_sees_derived_score_and_it_is_persisted(): void
    {
        $this->student->givePermissionTo(['assessment.view']);
        $this->actingAs($this->student);

        Attendance::factory()->create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Present,
        ]);

        Livewire::test(AssessmentAttendanceShow::class, ['assessment' => $this->assessment])
            ->assertSee('100%')
            ->assertSee('10.0');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('assessment_scores', ['score' => 10]);
    }

    public function test_teacher_sees_per_student_summary(): void
    {
        $this->teacher->givePermissionTo(['assessment.view']);
        $this->actingAs($this->teacher);

        Attendance::factory()->create([
            'session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'status' => AttendanceStatus::Absent,
        ]);

        Livewire::test(AssessmentAttendanceShow::class, ['assessment' => $this->assessment])
            ->assertSee($this->student->name)
            ->assertSee('0%');
    }

    public function test_non_attendance_assessment_404s(): void
    {
        $this->teacher->givePermissionTo(['assessment.view']);
        $this->actingAs($this->teacher);

        $other = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::TheoryQuiz]);

        Livewire::test(AssessmentAttendanceShow::class, ['assessment' => $other])
            ->assertStatus(404);
    }
}
