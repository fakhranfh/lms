<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\RoleName;
use App\Livewire\Courses\PeopleIndex;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PeopleIndexTest extends TestCase
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

        $teacherRole = Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->teacher->assignRole($teacherRole);

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);

        CoursePerson::factory()->for($this->course)->teacher()->create(['user_id' => $this->teacher->id]);
        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);
    }

    public function test_user_without_permission_cannot_access(): void
    {
        $this->actingAs($this->student);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_user_cannot_access_for_different_school_course(): void
    {
        $this->teacher->givePermissionTo('people.view');
        $this->actingAs($this->teacher);

        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        Livewire::test(PeopleIndex::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }

    public function test_teacher_sees_teachers_list(): void
    {
        $this->teacher->givePermissionTo('people.view');
        $this->actingAs($this->teacher);

        Livewire::test(PeopleIndex::class, ['course' => $this->course, 'activeSubTab' => 'teachers'])
            ->call('loadData')
            ->assertSee($this->teacher->name);
    }

    public function test_teacher_sees_students_list(): void
    {
        $this->teacher->givePermissionTo('people.view');
        $this->actingAs($this->teacher);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('selectSubTab', 'students')
            ->assertSee($this->student->name);
    }

    public function test_student_can_see_students_sub_tab(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertSet('activeSubTab', 'students')
            ->assertSee($this->student->name);
    }

    public function test_deep_links_directly_to_groups_sub_tab(): void
    {
        $this->teacher->givePermissionTo('people.view');
        $this->actingAs($this->teacher);

        Livewire::test(PeopleIndex::class, ['course' => $this->course, 'activeSubTab' => 'groups'])
            ->assertSet('activeSubTab', 'groups');
    }

    public function test_student_sees_own_group_only(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        $group = Group::factory()->for($this->course)->create(['name' => 'Team Alpha']);
        GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);

        Livewire::test(PeopleIndex::class, ['course' => $this->course, 'activeSubTab' => 'groups'])
            ->call('loadData')
            ->assertSee('Team Alpha');
    }

    public function test_student_without_group_sees_empty_state(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        Livewire::test(PeopleIndex::class, ['course' => $this->course, 'activeSubTab' => 'groups'])
            ->call('loadData')
            ->assertSee('You are not in a group yet.');
    }

    public function test_group_count_for_student_reflects_only_their_own_group(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        Group::factory()->for($this->course)->create();
        $ownGroup = Group::factory()->for($this->course)->create();
        GroupMember::factory()->for($ownGroup)->create(['user_id' => $this->student->id]);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertViewHas('groupsCount', 1);
    }

    public function test_group_count_for_teacher_reflects_all_course_groups(): void
    {
        $this->teacher->givePermissionTo('people.view');
        $this->actingAs($this->teacher);

        Group::factory()->for($this->course)->count(2)->create();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->assertViewHas('groupsCount', 2);
    }

    public function test_student_cannot_manage_groups(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->set('newGroupName', 'Team Alpha')
            ->call('createGroup')
            ->assertStatus(403);
    }

    public function test_teacher_can_create_group(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->set('newGroupName', 'Team Alpha')
            ->call('createGroup');

        $this->assertDatabaseHas('groups', ['course_id' => $this->course->id, 'name' => 'Team Alpha']);
    }

    public function test_teacher_can_rename_group(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create(['name' => 'Old Name']);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('startRename', $group->id)
            ->set('renameValue', 'New Name')
            ->call('saveRename');

        $this->assertDatabaseHas('groups', ['id' => $group->id, 'name' => 'New Name']);
    }

    public function test_teacher_can_delete_empty_group(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('deleteGroup', $group->id);

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function test_delete_blocked_when_group_has_members(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();
        GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('loadData')
            ->call('deleteGroup', $group->id)
            ->assertSee('Remove all members');

        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_add_student_assigns_to_group(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('addStudent', $group->id, $this->student->id);

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $this->student->id]);
    }

    public function test_moving_student_removes_from_previous_group_one_group_per_course(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $groupA = Group::factory()->for($this->course)->create(['name' => 'Group A']);
        $groupB = Group::factory()->for($this->course)->create(['name' => 'Group B']);
        GroupMember::factory()->for($groupA)->create(['user_id' => $this->student->id]);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('addStudent', $groupB->id, $this->student->id);

        $this->assertDatabaseMissing('group_members', ['group_id' => $groupA->id, 'user_id' => $this->student->id]);
        $this->assertDatabaseHas('group_members', ['group_id' => $groupB->id, 'user_id' => $this->student->id]);
    }

    public function test_student_cannot_enroll_teacher(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        $newTeacher = User::factory()->forSchool($this->school)->create();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('enrollTeacher', $newTeacher->id)
            ->assertStatus(403);
    }

    public function test_teacher_can_enroll_another_teacher(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $newTeacher = User::factory()->forSchool($this->school)->create();
        $newTeacher->assignRole(Role::where('name', RoleName::Teacher->value)->where('school_id', $this->school->id)->first());

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('enrollTeacher', $newTeacher->id);

        $this->assertDatabaseHas('course_people', [
            'course_id' => $this->course->id,
            'user_id' => $newTeacher->id,
            'role_in_course' => 'teacher',
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_enroll_a_student(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $newStudent = User::factory()->forSchool($this->school)->create();
        $newStudent->assignRole(Role::where('name', RoleName::Student->value)->where('school_id', $this->school->id)->first());

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('enrollStudent', $newStudent->id);

        $this->assertDatabaseHas('course_people', [
            'course_id' => $this->course->id,
            'user_id' => $newStudent->id,
            'role_in_course' => 'student',
            'status' => 'active',
        ]);
    }

    public function test_cannot_enroll_a_user_without_the_teacher_role_as_teacher(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $plainUser = User::factory()->forSchool($this->school)->create();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('enrollTeacher', $plainUser->id);

        $this->assertDatabaseMissing('course_people', [
            'course_id' => $this->course->id,
            'user_id' => $plainUser->id,
        ]);
    }

    public function test_cannot_enroll_a_user_without_the_student_role_as_student(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $plainUser = User::factory()->forSchool($this->school)->create();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('enrollStudent', $plainUser->id);

        $this->assertDatabaseMissing('course_people', [
            'course_id' => $this->course->id,
            'user_id' => $plainUser->id,
        ]);
    }

    public function test_teacher_search_results_exclude_already_enrolled_teachers(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $matchingButEnrolled = $this->teacher;

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->set('teacherSearch', $matchingButEnrolled->name)
            ->assertDontSee($matchingButEnrolled->email);
    }

    public function test_teacher_search_results_exclude_users_without_teacher_role(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $studentOnly = $this->student;

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->set('teacherSearch', $studentOnly->name)
            ->assertDontSee($studentOnly->email);
    }

    public function test_student_search_results_exclude_users_without_student_role(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $teacherOnly = $this->teacher;

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->set('studentSearch', $teacherOnly->name)
            ->assertDontSee($teacherOnly->email);
    }

    public function test_student_cannot_unenroll_teacher(): void
    {
        $this->student->givePermissionTo('people.view');
        $this->actingAs($this->student);

        $coursePerson = CoursePerson::where('course_id', $this->course->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('unenrollTeacher', $coursePerson->id)
            ->assertStatus(403);
    }

    public function test_teacher_can_unenroll_a_teacher(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $coursePerson = CoursePerson::where('course_id', $this->course->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('unenrollTeacher', $coursePerson->id);

        $this->assertDatabaseMissing('course_people', ['id' => $coursePerson->id]);
    }

    public function test_teacher_can_unenroll_a_student(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $coursePerson = CoursePerson::where('course_id', $this->course->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('unenrollStudent', $coursePerson->id);

        $this->assertDatabaseMissing('course_people', ['id' => $coursePerson->id]);
    }

    public function test_remove_student_from_group(): void
    {
        $this->teacher->givePermissionTo(['people.view', 'groups.manage']);
        $this->actingAs($this->teacher);

        $group = Group::factory()->for($this->course)->create();
        $member = GroupMember::factory()->for($group)->create(['user_id' => $this->student->id]);

        Livewire::test(PeopleIndex::class, ['course' => $this->course])
            ->call('removeStudent', $member->id);

        $this->assertDatabaseMissing('group_members', ['id' => $member->id]);
    }
}
