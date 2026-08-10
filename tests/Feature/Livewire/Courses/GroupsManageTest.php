<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\RoleName;
use App\Livewire\Courses\GroupsManage;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class GroupsManageTest extends TestCase
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

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_student_cannot_access(): void
    {
        $this->student->givePermissionTo('sessions.view');
        $this->actingAs($this->student);

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_teacher_can_create_group(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->set('newGroupName', 'Team Alpha')
            ->call('createGroup');

        $this->assertDatabaseHas('groups', ['course_id' => $this->course->id, 'name' => 'Team Alpha']);
    }

    public function test_teacher_can_rename_group(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create(['name' => 'Old Name']);

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->call('startRename', $group->id)
            ->set('renameValue', 'New Name')
            ->call('saveRename');

        $this->assertDatabaseHas('groups', ['id' => $group->id, 'name' => 'New Name']);
    }

    public function test_teacher_can_delete_empty_group(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->call('deleteGroup', $group->id);

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function test_delete_blocked_when_group_has_members(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();
        GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->call('deleteGroup', $group->id)
            ->assertSee('Remove all members');

        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_add_student_assigns_to_group(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->call('addStudent', $group->id, $this->student->id);

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $this->student->id]);
    }

    public function test_moving_student_removes_from_previous_group_one_group_per_course(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        $groupA = Group::factory()->for($this->course)->create(['name' => 'Group A']);
        $groupB = Group::factory()->for($this->course)->create(['name' => 'Group B']);
        GroupMember::factory()->for($groupA)->create(['user_id' => $this->student->id]);

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->call('addStudent', $groupB->id, $this->student->id);

        $this->assertDatabaseMissing('group_members', ['group_id' => $groupA->id, 'user_id' => $this->student->id]);
        $this->assertDatabaseHas('group_members', ['group_id' => $groupB->id, 'user_id' => $this->student->id]);
    }

    public function test_remove_student_from_group(): void
    {
        $this->teacher->givePermissionTo('groups.manage');
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();
        $member = GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);

        Livewire::test(GroupsManage::class, ['course' => $this->course])
            ->call('removeStudent', $member->id);

        $this->assertDatabaseMissing('group_members', ['id' => $member->id]);
    }
}
