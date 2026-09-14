<?php

namespace App\Livewire\Courses;

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Session;
use App\Services\AttendanceDerivationService;
use App\Services\AttendanceDraftService;
use App\Services\AttendanceService;
use App\Services\CoursePersonService;
use App\Services\SessionService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceIndex extends Component
{
    use WithPagination;

    private const STUDENTS_PER_PAGE = 10;

    public Course $course;

    public bool $isStudent = false;

    public bool $dataLoaded = false;

    #[Url(as: 'session')]
    public ?string $selectedSessionId = null;

    #[Url(as: 'q')]
    public string $studentSearch = '';

    /**
     * In-progress "mark attendance" edits for the selected session, keyed
     * by user id, e.g. ['u1' => ['status' => 'present', 'notes' => '']].
     * Mirrored to Redis via AttendanceDraftService so they survive a page
     * refresh, not just Livewire's own pagination round-trips.
     *
     * @var array<string, array{status?: string, notes?: string}>
     */
    public array $drafts = [];

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
        $this->resetPage();
    }

    public function updatingStudentSearch(): void
    {
        $this->resetPage();
    }

    public function recordAttendance(string $sessionId, string $userId, string $status, string $notes, AttendanceService $attendanceService, AttendanceDraftService $attendanceDraftService): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        $session = $this->course->sessions()->whereKey($sessionId)->first();

        if (! $session) {
            $this->errorMessage = __('Session not found.');

            return;
        }

        abort_if($session->isAttendanceLocked(), 403);

        $this->persistAttendance($session->id, $userId, $status, $notes, $attendanceService);

        $attendanceDraftService->forgetUser($session->id, $userId);
        unset($this->drafts[$userId]);

        $this->successMessage = __('Attendance recorded.');
    }

    /**
     * Persists every drafted status/notes change for the selected session in
     * one go, then locks the session so it can no longer be edited — backs
     * the sticky "Save All" footer, which the UI only reaches after the
     * user confirms the "this can't be changed afterwards" warning.
     */
    public function saveAllAttendance(AttendanceService $attendanceService, AttendanceDraftService $attendanceDraftService, SessionService $sessionService): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        $this->errorMessage = null;

        $session = $this->selectedSessionId
            ? $this->course->sessions()->whereKey($this->selectedSessionId)->first()
            : null;

        if (! $session) {
            $this->errorMessage = __('Session not found.');

            return;
        }

        abort_if($session->isAttendanceLocked(), 403);

        foreach ($this->drafts as $userId => $draft) {
            if (! isset($draft['status'])) {
                continue;
            }

            $this->persistAttendance($session->id, $userId, $draft['status'], $draft['notes'] ?? '', $attendanceService);
        }

        $sessionService->update($session->id, ['attendance_locked_at' => now()]);

        $attendanceDraftService->clear($session->id);
        $this->drafts = [];

        $this->successMessage = __('Attendance saved and locked — it can no longer be changed.');
    }

    private function persistAttendance(string $sessionId, string $userId, string $status, string $notes, AttendanceService $attendanceService): void
    {
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
    }

    /**
     * Mirrors every "mark attendance" edit to Redis as it happens, so a
     * page refresh (not just Livewire's own pagination) never loses it.
     */
    public function updated(string $name, mixed $value, AttendanceDraftService $attendanceDraftService): void
    {
        if (! $this->selectedSessionId || ! str_starts_with($name, 'drafts.')) {
            return;
        }

        [$userId, $attribute] = explode('.', substr($name, strlen('drafts.')), 2);

        $attendanceDraftService->save($this->selectedSessionId, $userId, $attribute, (string) $value);
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    /**
     * Dev-only helper to wipe every attendance record for this course,
     * unlock all of its sessions, and clear any pending drafts — so a
     * developer can re-test the mark-attendance flow from a clean slate.
     */
    public function resetAllAttendance(AttendanceService $attendanceService, AttendanceDraftService $attendanceDraftService, SessionService $sessionService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('attendance.manage'), 403);

        $attendanceService->deleteForCourse($this->course->id);

        foreach ($this->course->sessions()->get() as $session) {
            $sessionService->update($session->id, ['attendance_locked_at' => null]);
            $attendanceDraftService->clear($session->id);
        }

        $this->drafts = [];
        $this->successMessage = __('All student attendance for this course has been reset.');
    }

    public function render(
        AttendanceDerivationService $attendanceDerivationService,
        AttendanceService $attendanceService,
        CoursePersonService $coursePersonService,
        AttendanceDraftService $attendanceDraftService,
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

        $sessions = $attendanceDerivationService->sessionsForCourse($this->course)
            ->filter(fn (Session $session) => in_array($session->delivery_mode, [DeliveryMode::VirtualClass, DeliveryMode::Offline], true))
            ->values();

        if ($this->isStudent) {
            $userId = auth()->id();
            $viewData['summary'] = $attendanceDerivationService->summaryForStudent($this->course, $userId);

            $viewData['sessionRows'] = $sessions->map(function ($session) use ($attendanceDerivationService, $userId) {
                return [
                    'session' => $session,
                    'attend' => $attendanceDerivationService->isSessionAttended($session, $userId),
                    'requirement' => $attendanceDerivationService->attendanceRequirementDescriptionForSession($session),
                ];
            });
        } else {
            $viewData['sessions'] = $sessions;
            $selectedSession = $this->selectedSessionId
                ? $sessions->firstWhere('id', $this->selectedSessionId)
                : $sessions->first();
            $viewData['selectedSession'] = $selectedSession;
            $viewData['isLocked'] = $selectedSession?->isAttendanceLocked() ?? false;

            if ($selectedSession) {
                $this->selectedSessionId = $selectedSession->id;
                $this->drafts = $attendanceDraftService->all($selectedSession->id);
            }

            $students = $coursePersonService->studentsForCourse($this->course->id);

            $search = trim($this->studentSearch);

            if ($search !== '') {
                $students = $students->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )->values();
            }

            $viewData['studentRows'] = $selectedSession
                ? $this->paginateStudentRows($students, $selectedSession, $attendanceDerivationService, $attendanceService)
                : new LengthAwarePaginator([], 0, self::STUDENTS_PER_PAGE);

            $viewData['statuses'] = AttendanceStatus::cases();
        }

        return view('livewire.courses.attendance-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     */
    private function paginateStudentRows(
        Collection $students,
        Session $selectedSession,
        AttendanceDerivationService $attendanceDerivationService,
        AttendanceService $attendanceService,
    ): LengthAwarePaginator {
        $page = $this->getPage();

        $rows = $students->forPage($page, self::STUDENTS_PER_PAGE)->map(function ($coursePerson) use ($selectedSession, $attendanceDerivationService, $attendanceService) {
            $attendance = $attendanceService->findBySessionAndUser($selectedSession->id, $coursePerson->user_id);

            $this->drafts[$coursePerson->user_id]['status'] ??= $attendance !== null ? $attendance->status->value : 'absent';
            $this->drafts[$coursePerson->user_id]['notes'] ??= $attendance !== null ? ($attendance->notes ?? '') : '';

            return [
                'user' => $coursePerson->user,
                'attend' => $attendanceDerivationService->isSessionAttended($selectedSession, $coursePerson->user_id),
                'requirement' => $attendanceDerivationService->attendanceRequirementDescriptionForSession($selectedSession),
                'selfAttendedAt' => $attendanceDerivationService->selfAttendedAt($selectedSession, $coursePerson->user_id),
                'teacherRecordedAt' => ($attendance !== null && $attendance->status === AttendanceStatus::Present)
                    ? $attendance->recorded_at_display
                    : null,
            ];
        })->values();

        return new LengthAwarePaginator(
            $rows,
            $students->count(),
            self::STUDENTS_PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }
}
