<?php

namespace Tests\Feature\Console\Groups;

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Tests\TestCase;

class AutoAssignUnassignedStudentsCommandTest extends TestCase
{
    private School $school;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->course = Course::factory()->for($this->school)->create();
    }

    private function makeStudent(): User
    {
        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $student = User::factory()->forSchool($this->school)->create();
        $student->assignRole($studentRole);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $student->id]);

        return $student;
    }

    public function test_it_ignores_courses_before_h_minus_7(): void
    {
        Session::factory()->for($this->course)->create(['date_start' => now()->addDays(10)]);
        $student = $this->makeStudent();

        $this->artisan('groups:auto-assign');

        $this->assertDatabaseMissing('group_members', ['user_id' => $student->id]);
    }

    public function test_it_fills_existing_group_with_room_at_h_minus_7(): void
    {
        Session::factory()->for($this->course)->create(['date_start' => now()->addDays(5)]);

        $group = Group::factory()->for($this->course)->create(['target_size' => 2]);
        $existingMember = $this->makeStudent();
        GroupMember::factory()->for($group)->create(['user_id' => $existingMember->id]);

        $unassigned = $this->makeStudent();

        $this->artisan('groups:auto-assign');

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $unassigned->id]);
    }

    public function test_it_creates_a_new_group_when_existing_groups_are_full(): void
    {
        Session::factory()->for($this->course)->create(['date_start' => now()->addDays(1)]);

        $group = Group::factory()->for($this->course)->create(['name' => 'Group 1', 'target_size' => 1]);
        $existingMember = $this->makeStudent();
        GroupMember::factory()->for($group)->create(['user_id' => $existingMember->id]);

        $unassigned = $this->makeStudent();

        $this->artisan('groups:auto-assign');

        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $unassigned->id]);
        $this->assertDatabaseHas('groups', ['course_id' => $this->course->id, 'name' => 'Group 2']);

        $newGroup = Group::where('course_id', $this->course->id)->where('name', 'Group 2')->firstOrFail();
        $this->assertDatabaseHas('group_members', ['group_id' => $newGroup->id, 'user_id' => $unassigned->id]);
    }
}
