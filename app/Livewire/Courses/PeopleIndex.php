<?php

namespace App\Livewire\Courses;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Enums\RoleName;
use App\Models\Course;
use App\Models\CoursePerson;
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

    public string $renameValue = '';

    public string $groupMemberSearch = '';

    public string $groupSearchQuery = '';

    public ?string $errorMessage = null;

    public string $teacherSearch = '';

    public string $studentSearch = '';

    public int $generateStudentCount = 5;

    public int $groupSize = 4;

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

    public function generateGroups(CoursePersonService $coursePersonService, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $this->validate([
            'groupSize' => 'required|integer|min:1|max:100',
        ]);

        $students = $coursePersonService->studentsForCourse($this->course->id);
        $groups = $groupService->forCourse($this->course->id);
        $assignedUserIds = $groups->flatMap(fn ($group) => $group->members->pluck('user_id'))->all();

        $unassignedUserIds = $students
            ->reject(fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true))
            ->pluck('user_id')
            ->shuffle()
            ->values();

        if ($unassignedUserIds->isEmpty()) {
            return;
        }

        $nextNumber = $groups->count() + 1;

        foreach ($unassignedUserIds->chunk($this->groupSize) as $chunk) {
            $group = $groupService->create([
                'course_id' => $this->course->id,
                'name' => "Group {$nextNumber}",
                'created_by' => auth()->id(),
                'target_size' => $this->groupSize,
            ]);

            foreach ($chunk as $userId) {
                $groupMemberService->create([
                    'group_id' => $group->id,
                    'user_id' => $userId,
                    'joined_at' => now(),
                ]);
            }

            $nextNumber++;
        }
    }

    public function saveRename(string $groupId, string $name, GroupService $groupService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $group = $groupService->find($groupId);

        if (! $group || $group->course_id !== $this->course->id) {
            return;
        }

        $this->renameValue = $name;

        $this->validate([
            'renameValue' => 'required|string|max:255',
        ]);

        $groupService->update($groupId, ['name' => $this->renameValue]);
    }

    public function deleteGroup(string $groupId, GroupService $groupService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $group = $groupService->find($groupId);

        if (! $group || $group->course_id !== $this->course->id) {
            $this->errorMessage = __('Group not found.');

            return;
        }

        $groupService->delete($groupId);
    }

    public function deleteAllGroups(GroupService $groupService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $groups = $groupService->forCourse($this->course->id);

        foreach ($groups as $group) {
            $groupService->delete($group->id);
        }
    }

    public function addStudent(string $groupId, string $userId, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $group = $groupService->find($groupId);

        if (! $group || $group->course_id !== $this->course->id) {
            return;
        }

        $alreadyAssignedInCourse = $groupMemberService->get(['user_id' => $userId])
            ->contains(fn ($member) => $member->group->course_id === $this->course->id);

        if ($alreadyAssignedInCourse) {
            return;
        }

        $groupMemberService->create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'joined_at' => now(),
        ]);
    }

    public function moveStudent(string $groupId, string $userId, GroupService $groupService, GroupMemberService $groupMemberService): void
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
    }

    /**
     * @return array{unassigned: Collection<int, CoursePerson>, assignedElsewhere: Collection<int, CoursePerson>}
     */
    public function candidateStudentsForGroup(string $groupId): array
    {
        $group = app(GroupService::class)->find($groupId, ['members']);
        $currentMemberUserIds = $group?->members->pluck('user_id')->all() ?? [];

        $students = app(CoursePersonService::class)->studentsForCourse($this->course->id);
        $groups = app(GroupService::class)->forCourse($this->course->id);
        $assignedUserIds = $groups->flatMap(fn ($g) => $g->members->pluck('user_id'))->all();

        $search = trim($this->groupMemberSearch);

        $candidates = $students
            ->reject(fn ($coursePerson) => in_array($coursePerson->user_id, $currentMemberUserIds, true))
            ->when(
                $search !== '',
                fn ($collection) => $collection->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )
            );

        return [
            'unassigned' => $candidates->reject(fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true))->values(),
            'assignedElsewhere' => $candidates->filter(fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true))->values(),
        ];
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

    public function generateStudents(CoursePersonService $coursePersonService, RoleService $roleService): void
    {
        abort_unless(app()->environment('local', 'testing') && auth()->user()->can('groups.manage'), 403);

        $this->validate([
            'generateStudentCount' => 'required|integer|min:1|max:100',
        ]);

        $studentRoleId = $this->schoolRoleId(RoleName::Student);

        if ($studentRoleId === null) {
            $this->errorMessage = __('Student role not found for this school.');

            return;
        }

        $studentRole = $roleService->find($studentRoleId);

        if (! $studentRole instanceof Role) {
            $this->errorMessage = __('Student role not found for this school.');

            return;
        }

        $students = User::factory()->forSchool($this->course->school)->count($this->generateStudentCount)->create();

        $students->each(function (User $user) use ($studentRole, $coursePersonService) {
            $user->assignRole($studentRole);

            $coursePersonService->enroll($this->course->id, $user->id, [
                'role_in_course' => RoleInCourse::Student,
                'enrolled_at' => now(),
                'status' => CourseMembershipStatus::Active,
            ]);
        });
    }

    /**
     * @param  array<int, string>  $coursePersonIds
     */
    public function bulkUnenrollStudents(array $coursePersonIds): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $coursePersonService = app(CoursePersonService::class);

        foreach ($coursePersonIds as $coursePersonId) {
            $coursePerson = $coursePersonService->find($coursePersonId);

            if ($coursePerson && $coursePerson->course_id === $this->course->id && $coursePerson->role_in_course === RoleInCourse::Student) {
                $coursePersonService->delete($coursePersonId);
            }
        }
    }

    /**
     * @param  array<int, string>  $coursePersonIds
     */
    public function bulkUnenrollTeachers(array $coursePersonIds): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $coursePersonService = app(CoursePersonService::class);

        foreach ($coursePersonIds as $coursePersonId) {
            $coursePerson = $coursePersonService->find($coursePersonId);

            if ($coursePerson && $coursePerson->course_id === $this->course->id && $coursePerson->role_in_course === RoleInCourse::Teacher && $coursePerson->user_id !== auth()->id()) {
                $coursePersonService->delete($coursePersonId);
            }
        }
    }

    public function unenrollTeacher(string $coursePersonId): void
    {
        abort_unless(auth()->user()->can('groups.manage'), 403);

        $coursePersonService = app(CoursePersonService::class);
        $coursePerson = $coursePersonService->find($coursePersonId);

        if (! $coursePerson || $coursePerson->course_id !== $this->course->id || $coursePerson->role_in_course !== RoleInCourse::Teacher) {
            return;
        }

        if ($coursePerson->user_id === auth()->id()) {
            $this->errorMessage = __('You cannot remove yourself from this course.');

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
            'canGenerateStudents' => $canManageGroups && app()->environment('local', 'testing'),
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

            $unassignedStudents = $students->reject(
                fn ($coursePerson) => in_array($coursePerson->user_id, $assignedUserIds, true)
            );

            $searchQuery = strtolower(trim($this->groupSearchQuery));

            $viewData['groups'] = $groups;
            $viewData['visibleGroups'] = $searchQuery === ''
                ? $groups
                : $groups->filter(
                    fn ($group) => str_contains(strtolower($group->name), $searchQuery)
                        || $group->members->contains(
                            fn ($member) => str_contains(strtolower($member->user->name), $searchQuery)
                        )
                )->values();

            $viewData['unassignedStudents'] = $unassignedStudents;
            $viewData['visibleUnassignedStudents'] = $searchQuery === ''
                ? $unassignedStudents
                : $unassignedStudents->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), $searchQuery)
                )->values();
        }

        return view('livewire.courses.people-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
