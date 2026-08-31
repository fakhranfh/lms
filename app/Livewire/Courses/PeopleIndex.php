<?php

namespace App\Livewire\Courses;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Enums\RoleName;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Services\CoursePersonService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Services\RoleService;
use App\Services\UserService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class PeopleIndex extends Component
{
    private const SUB_TABS = ['teachers', 'students', 'groups'];

    private const SUGGESTION_LIMIT = 5;

    public Course $course;

    public bool $isStudent = false;

    public bool $dataLoaded = false;

    #[Url(as: 'tab', history: true)]
    public string $activeSubTab = 'students';

    public string $newGroupName = '';

    public ?string $renamingGroupId = null;

    public string $renameValue = '';

    public ?string $assigningGroupId = null;

    public ?string $errorMessage = null;

    public string $teacherSearch = '';

    public string $studentSearch = '';

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('people.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if (! in_array($this->activeSubTab, self::SUB_TABS, true)) {
            $this->activeSubTab = 'students';
        }
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function selectSubTab(string $tab): void
    {
        if (in_array($tab, self::SUB_TABS, true)) {
            $this->activeSubTab = $tab;
        }
    }

    public function createGroup(GroupService $groupService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

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
        abort_unless(auth()->user()->can('groups.manage'), 403);

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
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $this->validate([
            'renameValue' => 'required|string|max:255',
        ]);

        abort_unless($this->renamingGroupId !== null, 404);

        $groupService->update($this->renamingGroupId, ['name' => $this->renameValue]);

        $this->cancelRename();
    }

    public function deleteGroup(string $groupId, GroupService $groupService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

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
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $this->assigningGroupId = $groupId;
    }

    public function cancelAssigning(): void
    {
        $this->assigningGroupId = null;
    }

    public function addStudent(string $groupId, string $userId, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

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
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $groupMemberService->delete($groupMemberId);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function teacherSearchResults(): Collection
    {
        return $this->searchAvailableUsers($this->teacherSearch, RoleInCourse::Teacher);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function studentSearchResults(): Collection
    {
        return $this->searchAvailableUsers($this->studentSearch, RoleInCourse::Student);
    }

    /**
     * @return Collection<int, User>
     */
    private function searchAvailableUsers(string $search, RoleInCourse $role): Collection
    {
        $roleName = $role === RoleInCourse::Teacher ? RoleName::Teacher : RoleName::Student;
        $roleId = $this->schoolRoleId($roleName);

        if ($roleId === null) {
            return new Collection;
        }

        $enrolledUserIds = app(CoursePersonService::class)
            ->get(['course_id' => $this->course->id, 'role_in_course' => $role->value])
            ->pluck('user_id');

        return app(UserService::class)
            ->paginate(['search' => $search, 'role_id' => $roleId], [], self::SUGGESTION_LIMIT)
            ->getCollection()
            ->reject(fn ($user) => $enrolledUserIds->contains($user->id))
            ->take(self::SUGGESTION_LIMIT);
    }

    private function schoolRoleId(RoleName $roleName): ?int
    {
        $role = app(RoleService::class)
            ->get(['name' => $roleName->value, 'school_id' => $this->course->school_id])
            ->first();

        return $role instanceof Role ? $role->id : null;
    }

    public function enrollTeacher(string $userId): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $user = app(UserService::class)->find($userId);

        if (! $user || ! $user->hasRole(RoleName::Teacher)) {
            return;
        }

        app(CoursePersonService::class)->enroll($this->course->id, $userId, [
            'role_in_course' => RoleInCourse::Teacher,
            'enrolled_at' => now(),
            'status' => CourseMembershipStatus::Active,
        ]);

        $this->teacherSearch = '';
        unset($this->teacherSearchResults);
    }

    public function enrollStudent(string $userId): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $user = app(UserService::class)->find($userId);

        if (! $user || ! $user->hasRole(RoleName::Student)) {
            return;
        }

        app(CoursePersonService::class)->enroll($this->course->id, $userId, [
            'role_in_course' => RoleInCourse::Student,
            'enrolled_at' => now(),
            'status' => CourseMembershipStatus::Active,
        ]);

        $this->studentSearch = '';
        unset($this->studentSearchResults);
    }

    public function unenrollTeacher(string $coursePersonId): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $coursePersonService = app(CoursePersonService::class);
        $coursePerson = $coursePersonService->find($coursePersonId);

        if (! $coursePerson || $coursePerson->course_id !== $this->course->id || $coursePerson->role_in_course !== RoleInCourse::Teacher) {
            return;
        }

        $coursePersonService->delete($coursePersonId);
    }

    public function unenrollStudent(string $coursePersonId): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $coursePersonService = app(CoursePersonService::class);
        $coursePerson = $coursePersonService->find($coursePersonId);

        if (! $coursePerson || $coursePerson->course_id !== $this->course->id || $coursePerson->role_in_course !== RoleInCourse::Student) {
            return;
        }

        $coursePersonService->delete($coursePersonId);
    }

    public function render(CoursePersonService $coursePersonService, GroupService $groupService)
    {
        $canManageGroups = auth()->user()->can('groups.manage');

        $viewData = [
            'course' => $this->course,
            'isStudent' => $this->isStudent,
            'activeSubTab' => $this->activeSubTab,
            'canManageGroups' => $canManageGroups,
            'courseTabs' => CourseTabs::build($this->course, 'people'),
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.people-index-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $teachers = $coursePersonService->teachersForCourse($this->course->id);
        $students = $coursePersonService->studentsForCourse($this->course->id);
        $groups = $groupService->forCourse($this->course->id);

        $viewData['teachers'] = $teachers;
        $viewData['teachersCount'] = $teachers->count();
        $viewData['students'] = $students;
        $viewData['studentsCount'] = $students->count();

        if ($this->isStudent) {
            $ownGroup = $groups->first(
                fn ($group) => $group->members->contains(fn ($member) => $member->user_id === auth()->id())
            );

            $viewData['ownGroup'] = $ownGroup;
            $viewData['groupsCount'] = $ownGroup ? 1 : 0;
        } else {
            $viewData['groupsCount'] = $groups->count();
            $assignedUserIds = $groups->flatMap(fn ($group) => $group->members->pluck('user_id'))->all();

            $viewData['groups'] = $groups;
            $viewData['allStudents'] = $students;
            $viewData['assignedUserIds'] = $assignedUserIds;
            $viewData['unassignedStudents'] = $students->reject(
                fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true)
            );
        }

        return view('livewire.courses.people-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
