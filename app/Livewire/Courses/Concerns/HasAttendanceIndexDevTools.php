<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\AttendanceDraftService;
use App\Services\AttendanceService;
use App\Services\CoursePersonService;
use App\Services\SessionService;
use Carbon\Carbon;

trait HasAttendanceIndexDevTools
{
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

    /**
     * Dev-only helper to randomly mark every enrolled student's attendance
     * for the selected session with a random status and a random
     * recorded_at timestamp within the session's date_start/date_end range.
     * Leaves the session unlocked so it can still be reviewed/edited before
     * saving — lets a developer quickly populate realistic attendance data
     * for testing without clicking through the UI.
     */
    public function generateRandomAttendance(AttendanceService $attendanceService, CoursePersonService $coursePersonService, AttendanceDraftService $attendanceDraftService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
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

        $statuses = ['present', 'present', 'present', 'late', 'absent', 'excused'];

        foreach ($coursePersonService->studentsForCourse($this->course->id) as $coursePerson) {
            $status = $statuses[array_rand($statuses)];
            $recordedAt = $status === 'absent'
                ? null
                : $this->randomTimestampBetween($session->date_start, $session->date_end);

            // "present" simulates the student checking themselves in, so the
            // record is attributed to the student, not the teacher generating it.
            $recordedBy = $status === 'present' ? $coursePerson->user_id : auth()->id();

            $this->persistAttendance($session->id, $coursePerson->user_id, $status, '', $attendanceService, $recordedAt, $recordedBy);

            $this->drafts[$coursePerson->user_id]['status'] = $status;
            $attendanceDraftService->save($session->id, $coursePerson->user_id, 'status', $status);
        }

        $this->successMessage = __('Random attendance generated for this session.');
    }

    private function randomTimestampBetween(Carbon $start, Carbon $end): Carbon
    {
        $randomSeconds = random_int(0, max(0, $end->diffInSeconds($start)));

        return $start->copy()->addSeconds($randomSeconds);
    }
}
