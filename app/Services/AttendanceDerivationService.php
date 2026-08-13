<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Models\Course;
use App\Models\Session;
use App\Models\VideoConference;
use Illuminate\Support\Collection;

/**
 * Derives per-session attendance and the overall attendance summary for a
 * student, per docs/tasks/course-restructure-progress.md.
 *
 * Fixed rule (no configurable requirements):
 * - Sessions with delivery_mode "online" are excluded from attendance entirely.
 * - "virtual_class" sessions: attended if the student has a
 *   VideoConferenceParticipation row on any of the session's video
 *   conferences (mere presence of a join record, no duration requirement),
 *   OR a Teacher manually marked them present.
 * - "offline" sessions: attended only if a Teacher manually marked them
 *   present. There is no auto-derivation for offline sessions.
 */
class AttendanceDerivationService
{
    public function __construct(
        private AttendanceService $attendanceService,
        private CourseAttendanceSettingService $courseAttendanceSettingService,
        private SessionService $sessionService,
    ) {}

    /**
     * Whether attendance applies to this session at all.
     */
    public function isAttendanceApplicable(Session $session): bool
    {
        return $session->delivery_mode !== DeliveryMode::Online;
    }

    public function isSessionAttended(Session $session, string $userId): bool
    {
        return match ($session->delivery_mode) {
            DeliveryMode::VirtualClass => $this->hasJoinedVideoConference($session, $userId) || $this->isManuallyCheckedIn($session, $userId),
            DeliveryMode::Offline => $this->isManuallyCheckedIn($session, $userId),
            DeliveryMode::Online => false,
        };
    }

    /**
     * How the student's attendance for this session was determined, for
     * display purposes. Null when the session is not attended.
     */
    public function attendanceSourceForSession(Session $session, string $userId): ?string
    {
        if ($session->delivery_mode === DeliveryMode::VirtualClass && $this->hasJoinedVideoConference($session, $userId)) {
            return 'video_conference';
        }

        if ($this->isManuallyCheckedIn($session, $userId)) {
            return 'manual';
        }

        return null;
    }

    /**
     * @return Collection<int, Session>
     */
    public function applicableSessionsForCourse(Course $course): Collection
    {
        return $this->sessionService->forCourse($course->id, ['videoConferences.participations'])
            ->filter(fn (Session $session) => $this->isAttendanceApplicable($session))
            ->values();
    }

    private function hasJoinedVideoConference(Session $session, string $userId): bool
    {
        return $session->videoConferences->contains(
            fn (VideoConference $conference) => $conference->participations->contains('user_id', $userId)
        );
    }

    private function isManuallyCheckedIn(Session $session, string $userId): bool
    {
        $attendance = $this->attendanceService->findBySessionAndUser($session->id, $userId);

        return $attendance !== null && $attendance->status === AttendanceStatus::Present;
    }

    /**
     * @return array{total_session: int, total_attendance: int, minimal_attendance: int}
     */
    public function summaryForStudent(Course $course, string $userId): array
    {
        $sessions = $this->applicableSessionsForCourse($course);
        $setting = $this->courseAttendanceSettingService->findByCourse($course->id);

        $totalAttendance = $sessions->filter(fn (Session $session) => $this->isSessionAttended($session, $userId))->count();

        return [
            'total_session' => $sessions->count(),
            'total_attendance' => $totalAttendance,
            'minimal_attendance' => $setting !== null ? $setting->minimal_attendance : 0,
        ];
    }
}
