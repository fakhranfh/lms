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

    private User $schoolAdmin;

    private User $teacher;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->schoolAdmin = User::factory()->forSchool($this->school)->create();
        $this->schoolAdmin->assignRole(Role::firstOrCreate(['name' => RoleName::SchoolAdmin->value, 'guard_name' => 'web', 'school_id' => $this->school->id]));
        $this->schoolAdmin->givePermissionTo('raport.view');
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

    public function test_school_admin_can_export_any_student_in_the_school(): void
    {
        $this->actingAs($this->schoolAdmin);

        $response = $this->get(route('raport.export.student', $this->student));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_cannot_export_a_student_even_when_granted_the_permission(): void
    {
        $this->teacher->givePermissionTo('raport.view');
        $this->actingAs($this->teacher);

        $response = $this->get(route('raport.export.student', $this->student));

        $response->assertStatus(403);
    }

    public function test_user_without_permission_cannot_export(): void
    {
        $this->actingAs($this->student);

        $this->get(route('raport.export.self'))->assertStatus(403);
    }
}
