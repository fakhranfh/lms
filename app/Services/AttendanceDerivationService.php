<?php

namespace App\Services;

use App\Enums\AttendanceRequirementType;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRequirement;
use App\Models\Course;
use App\Models\Session;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Derives per-session attendance requirement fulfillment and the overall
 * attendance summary for a student, per docs/tasks/course-restructure-attendance.md.
 */
class AttendanceDerivationService
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceRequirementService $attendanceRequirementService,
        private CourseAttendanceSettingService $courseAttendanceSettingService,
        private SessionService $sessionService,
        private ForumCommentService $forumCommentService,
    ) {}

    /**
     * @param  Collection<int, AttendanceRequirement>  $requirements
     * @return SupportCollection<int, array{requirement: AttendanceRequirement, is_fulfilled: bool}>
     */
    public function checklistForSession(Session $session, string $userId, Collection $requirements): SupportCollection
    {
        return $requirements->map(fn (AttendanceRequirement $requirement) => [
            'requirement' => $requirement,
            'is_fulfilled' => $this->isRequirementFulfilled($requirement, $session, $userId),
        ]);
    }

    public function isRequirementFulfilled(AttendanceRequirement $requirement, Session $session, string $userId): bool
    {
        return match ($requirement->requirement_type) {
            AttendanceRequirementType::ManualCheckin => $this->isManuallyCheckedIn($session, $userId),
            AttendanceRequirementType::ForumCompleted => $this->hasCompletedForum($session, $userId),
            AttendanceRequirementType::ClassDurationCompleted => $this->hasCompletedClassDuration($session, $userId),
        };
    }

    /**
     * A session is "attended" once all of its configured requirements are
     * fulfilled. With no requirements configured, we fall back to a plain
     * manual attendance record of status "present".
     *
     * @param  Collection<int, AttendanceRequirement>  $requirements
     */
    public function isSessionAttended(Session $session, string $userId, Collection $requirements): bool
    {
        if ($requirements->isEmpty()) {
            return $this->isManuallyCheckedIn($session, $userId);
        }

        return $requirements->every(fn (AttendanceRequirement $requirement) => $this->isRequirementFulfilled($requirement, $session, $userId));
    }

    private function isManuallyCheckedIn(Session $session, string $userId): bool
    {
        $attendance = $this->attendanceService->findBySessionAndUser($session->id, $userId);

        return $attendance !== null && $attendance->status === AttendanceStatus::Present;
    }

    private function hasCompletedForum(Session $session, string $userId): bool
    {
        $threshold = $session->required_forum_posts ?: 2;

        $count = $this->forumCommentService->countForUserInSession($userId, $session->id);

        return $count >= $threshold;
    }

    private function hasCompletedClassDuration(Session $session, string $userId): bool
    {
        $conferences = $session->videoConferences;

        if ($conferences->isEmpty()) {
            return true;
        }

        foreach ($conferences as $conference) {
            $requiredMinutes = $conference->required_duration_minutes;

            if (! $requiredMinutes) {
                continue;
            }

            $attendedMinutes = $conference->participations
                ->where('user_id', $userId)
                ->sum(fn ($participation) => $participation->left_at
                    ? $participation->joined_at->diffInMinutes($participation->left_at)
                    : 0);

            if ($attendedMinutes < $requiredMinutes) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{total_session: int, total_attendance: int, minimal_attendance: int}
     */
    public function summaryForStudent(Course $course, string $userId): array
    {
        $sessions = $this->sessionService->forCourse($course->id, ['videoConferences.participations']);
        $requirements = $this->attendanceRequirementService->forCourse($course->id);
        $setting = $this->courseAttendanceSettingService->findByCourse($course->id);

        $totalAttendance = $sessions->filter(fn (Session $session) => $this->isSessionAttended($session, $userId, $requirements))->count();

        return [
            'total_session' => $sessions->count(),
            'total_attendance' => $totalAttendance,
            'minimal_attendance' => $setting !== null ? $setting->minimal_attendance : 0,
        ];
    }
}
