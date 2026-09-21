<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Tests\TestCase;

class RaportExportControllerTest extends TestCase
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
        $this->student->assignRole(Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));
        $this->course = Course::factory()->for($this->school)->create();

        CoursePerson::factory()->for($this->course)->teacher()->create(['user_id' => $this->teacher->id]);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_student_can_export_their_own_raport(): void
    {
        $this->student->givePermissionTo('raport.view');
        $this->actingAs($this->student);

        $response = $this->get(route('raport.export.self'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_can_export_a_student_they_teach(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        $response = $this->get(route('raport.export.student', $this->student));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_cannot_export_a_student_they_do_not_teach(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        $otherStudent = User::factory()->forSchool($this->school)->create();

        $response = $this->get(route('raport.export.student', $otherStudent));

        $response->assertStatus(403);
    }

    public function test_user_without_permission_cannot_export(): void
    {
        $this->actingAs($this->student);

        $this->get(route('raport.export.self'))->assertStatus(403);
    }
}
