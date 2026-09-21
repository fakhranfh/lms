<?php

namespace App\Livewire;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\DashboardService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $isStudent = false;

    public bool $isSchoolAdmin = false;

    public bool $isTeacher = false;

    public int $totalStudents = 0;

    public int $totalTeachers = 0;

    public int $myCourses = 0;

    public int $todaySessions = 0;

    /**
     * Dashboard sections are loaded lazily via wire:init so the initial
     * page render is a cheap skeleton instead of blocking on the queries.
     */
    public bool $studentDataLoaded = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->isStudent = (bool) $user?->hasRole(RoleName::Student);
        $this->isSchoolAdmin = (bool) $user?->hasRole(RoleName::SchoolAdmin);
        $this->isTeacher = (bool) $user?->hasRole(RoleName::Teacher);

        if ($this->isSchoolAdmin) {
            $this->totalStudents = $this->countUsersWithRole(RoleName::Student);
            $this->totalTeachers = $this->countUsersWithRole(RoleName::Teacher);
        }

        if ($this->isTeacher) {
            $dashboardService = app(DashboardService::class);

            $this->myCourses = $dashboardService->countTeacherCourses($user->id);
            $this->todaySessions = $dashboardService->countTeacherSessionsToday($user->id);
        }
    }

    private function countUsersWithRole(RoleName $role): int
    {
        $schoolId = app(CurrentSchool::class)->getSchoolId() ?? Auth::user()?->school_id;

        if ($schoolId === null) {
            return 0;
        }

        $roleId = resolve(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => $role->value])
            ->first()?->id;

        if ($roleId === null) {
            return 0;
        }

        return count(resolve(UserService::class)->idsMatching(['role_id' => $roleId]));
    }

    public function loadStudentData(): void
    {
        $this->studentDataLoaded = true;
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->extends('layouts.app', ['topbarTitle' => 'Dashboard'])
            ->section('app-content');
    }
}
