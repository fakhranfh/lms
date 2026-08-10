<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class GroupsManage extends Component
{
    public Course $course;

    public string $newGroupName = '';

    public ?string $renamingGroupId = null;

    public string $renameValue = '';

    public ?string $assigningGroupId = null;

    public ?string $errorMessage = null;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('groups.manage') && $course->school_id === $schoolId, 403);

        $this->course = $course;
    }

    public function createGroup(GroupService $groupService): void
    {
        $this->validate([
            'newGroupName' => 'required|string|max:255',
        ]);

        $groupService->create([
            'course_id' => $this->course->id,
            'name' => $this->newGroupName,
            'created_by' => auth()->id(),
        ]);

        $this->newGroupName = '';
    }

    public function startRename(string $groupId, GroupService $groupService): void
    {
        $group = $groupService->find($groupId);

        if (! $group || $group->course_id !== $this->course->id) {
            return;
        }

        $this->renamingGroupId = $groupId;
        $this->renameValue = $group->name;
    }

    public function cancelRename(): void
    {
        $this->renamingGroupId = null;
        $this->renameValue = '';
    }

    public function saveRename(GroupService $groupService): void
    {
        $this->validate([
            'renameValue' => 'required|string|max:255',
        ]);

        abort_unless($this->renamingGroupId !== null, 404);

        $groupService->update($this->renamingGroupId, ['name' => $this->renameValue]);

        $this->cancelRename();
    }

    public function deleteGroup(string $groupId, GroupService $groupService): void
    {
        $group = $groupService->find($groupId, ['members']);

        if (! $group || $group->course_id !== $this->course->id) {
            $this->errorMessage = __('Group not found.');

            return;
        }

        if ($group->members->isNotEmpty()) {
            $this->errorMessage = __('Remove all members before deleting this group.');

            return;
        }

        $groupService->delete($groupId);
    }

    public function startAssigning(string $groupId): void
    {
        $this->assigningGroupId = $groupId;
    }

    public function cancelAssigning(): void
    {
        $this->assigningGroupId = null;
    }

    public function addStudent(string $groupId, string $userId, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        $group = $groupService->find($groupId);

        if (! $group || $group->course_id !== $this->course->id) {
            return;
        }

        $existingMemberships = $groupMemberService->get(['user_id' => $userId])
            ->filter(fn ($member) => $member->group->course_id === $this->course->id);

        foreach ($existingMemberships as $membership) {
            $groupMemberService->delete($membership->id);
        }

        $groupMemberService->create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'joined_at' => now(),
        ]);

        $this->assigningGroupId = null;
    }

    public function removeStudent(string $groupMemberId, GroupMemberService $groupMemberService): void
    {
        $groupMemberService->delete($groupMemberId);
    }

    public function render(GroupService $groupService, CoursePersonService $coursePersonService)
    {
        $groups = $groupService->forCourse($this->course->id);
        $students = $coursePersonService->studentsForCourse($this->course->id);

        $assignedUserIds = $groups->flatMap(fn ($group) => $group->members->pluck('user_id'))->all();
        $unassignedStudents = $students->reject(fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true));

        return view('livewire.courses.groups-manage', [
            'course' => $this->course,
            'groups' => $groups,
            'unassignedStudents' => $unassignedStudents,
            'allStudents' => $students,
            'assignedUserIds' => $assignedUserIds,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
