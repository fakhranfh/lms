<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\GradebookShow;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class GradebookShowTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private User $otherStudent;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->otherStudent = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->otherStudent->id]);
    }

    public function test_user_without_permission_cannot_view(): void
    {
        $this->actingAs($this->student);

        Livewire::test(GradebookShow::class, ['course' => $this->course, 'student' => $this->student])
            ->assertStatus(403);
    }

    public function test_student_can_view_own_gradebook(): void
    {
        $this->student->givePermissionTo('gradebook.view');
        $this->actingAs($this->student);

        Livewire::test(GradebookShow::class, ['course' => $this->course, 'student' => $this->student])
            ->call('loadData')
            ->assertSee($this->student->name)
            ->assertSee('Final Score');
    }

    public function test_student_cannot_view_another_students_gradebook(): void
    {
        $this->student->givePermissionTo('gradebook.view');
        $this->actingAs($this->student);

        Livewire::test(GradebookShow::class, ['course' => $this->course, 'student' => $this->otherStudent])
            ->assertStatus(403);
    }

    public function test_teacher_can_view_a_students_gradebook(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(GradebookShow::class, ['course' => $this->course, 'student' => $this->student])
            ->call('loadData')
            ->assertSee($this->student->name)
            ->assertSee('Gradebook');
    }

    public function test_non_enrolled_user_returns_not_found(): void
    {
        $this->teacher->givePermissionTo(['gradebook.view', 'gradebook.manage']);
        $this->actingAs($this->teacher);

        $notEnrolled = User::factory()->forSchool($this->school)->create();

        Livewire::test(GradebookShow::class, ['course' => $this->course, 'student' => $notEnrolled])
            ->assertStatus(404);
    }
}
