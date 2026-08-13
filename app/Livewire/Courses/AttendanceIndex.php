<?php

namespace App\Livewire\Courses;

use App\Enums\AttendanceStatus;
use App\Enums\RoleName;
use App\Models\Course;
use App\Services\AttendanceDerivationService;
use App\Services\AttendanceService;
use App\Services\CourseAttendanceSettingService;
use App\Services\CoursePersonService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AttendanceIndex extends Component
{
    public Course $course;

    public bool $isStudent = false;

    public bool $dataLoaded = false;

    public ?string $selectedSessionId = null;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('attendance.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function selectSession(string $sessionId): void
    {
        $this->selectedSessionId = $sessionId;
    }

    public function recordAttendance(string $sessionId, string $userId, string $status, string $notes, AttendanceService $attendanceService): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        $session = $this->course->sessions()->whereKey($sessionId)->first();

        if (! $session) {
            $this->errorMessage = __('Session not found.');

            return;
        }

        $existing = $attendanceService->findBySessionAndUser($sessionId, $userId);

        $data = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'status' => AttendanceStatus::from($status),
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
            'notes' => $notes ?: null,
        ];

        if ($existing) {
            $attendanceService->update($existing->id, $data);
        } else {
            $attendanceService->create($data);
        }

        $this->successMessage = __('Attendance recorded.');
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    public function render(
        CourseAttendanceSettingService $courseAttendanceSettingService,
        AttendanceDerivationService $attendanceDerivationService,
        AttendanceService $attendanceService,
        CoursePersonService $coursePersonService,
    ) {
        $viewData = [
            'course' => $this->course,
            'isStudent' => $this->isStudent,
            'canManage' => auth()->user()->can('attendance.manage'),
            'courseTabs' => CourseTabs::build($this->course, 'attendance'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.attendance-index-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $sessions = $attendanceDerivationService->applicableSessionsForCourse($this->course);

        if ($this->isStudent) {
            $userId = auth()->id();
            $viewData['summary'] = $attendanceDerivationService->summaryForStudent($this->course, $userId);

            $viewData['sessionRows'] = $sessions->map(function ($session) use ($attendanceDerivationService, $userId) {
                return [
                    'session' => $session,
                    'attend' => $attendanceDerivationService->isSessionAttended($session, $userId),
                    'source' => $attendanceDerivationService->attendanceSourceForSession($session, $userId),
                ];
            });
        } else {
            $viewData['sessions'] = $sessions;
            $selectedSession = $this->selectedSessionId
                ? $sessions->firstWhere('id', $this->selectedSessionId)
                : $sessions->first();
            $viewData['selectedSession'] = $selectedSession;

            $students = $coursePersonService->studentsForCourse($this->course->id);

            $viewData['studentRows'] = $selectedSession
                ? $students->map(function ($coursePerson) use ($selectedSession, $attendanceDerivationService, $attendanceService) {
                    return [
                        'user' => $coursePerson->user,
                        'attend' => $attendanceDerivationService->isSessionAttended($selectedSession, $coursePerson->user_id),
                        'source' => $attendanceDerivationService->attendanceSourceForSession($selectedSession, $coursePerson->user_id),
                        'attendance' => $attendanceService->findBySessionAndUser($selectedSession->id, $coursePerson->user_id),
                    ];
                })
                : collect();

            $viewData['statuses'] = AttendanceStatus::cases();
        }

        return view('livewire.courses.attendance-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
