<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\DeliveryMode;
use App\Models\Course;
use App\Models\Session;
use App\Models\VideoConference;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceDerivationService
{
    private const DEFAULT_REQUIRED_FORUM_POSTS = 2;

    public function __construct(
        private AttendanceService $attendanceService,
        private CourseAttendanceSettingService $courseAttendanceSettingService,
        private SessionService $sessionService,
        private ForumThreadService $forumThreadService,
        private ForumCommentService $forumCommentService,
    ) {}

    public function isSessionAttended(Session $session, string $userId): bool
    {
        return match ($session->delivery_mode) {
            DeliveryMode::VirtualClass => $this->hasJoinedVideoConference($session, $userId) || $this->isManuallyCheckedIn($session, $userId),
            DeliveryMode::Offline => $this->isManuallyCheckedIn($session, $userId),
            DeliveryMode::Online => $this->hasMetForumPostRequirement($session, $userId),
        };
    }

    public function attendanceRequirementDescriptionForSession(Session $session): string
    {
        return match ($session->delivery_mode) {
            DeliveryMode::VirtualClass => 'Join video conference',
            DeliveryMode::Offline => 'Teacher mark',
            DeliveryMode::Online => $this->requiredForumPosts($session).' forum posts',
        };
    }

    /**
     * @return Collection<int, Session>
     */
    public function sessionsForCourse(Course $course): Collection
    {
        return $this->sessionService->forCourse($course->id, ['videoConferences.participations'])->values();
    }

    private function hasJoinedVideoConference(Session $session, string $userId): bool
    {
        return $session->videoConferences->contains(
            fn (VideoConference $conference) => $conference->participations->contains('user_id', $userId)
        );
    }

    /**
     * The datetime a student joined a virtual class session's video
     * conference on their own, i.e. self-recorded attendance rather than a
     * teacher's manual mark. Null when the student never joined.
     */
    public function selfAttendedAt(Session $session, string $userId): ?CarbonInterface
    {
        foreach ($session->videoConferences as $conference) {
            $participation = $conference->participations->firstWhere('user_id', $userId);

            if ($participation !== null) {
                return $participation->joined_at_display;
            }
        }

        return null;
    }

    private function isManuallyCheckedIn(Session $session, string $userId): bool
    {
        $attendance = $this->attendanceService->findBySessionAndUser($session->id, $userId);

        return $attendance !== null && $attendance->status === AttendanceStatus::Present;
    }

    private function hasMetForumPostRequirement(Session $session, string $userId): bool
    {
        $threadCount = $this->forumThreadService->countForUserInSession($userId, $session->id);
        $commentCount = $this->forumCommentService->countForUserInSession($userId, $session->id);

        return ($threadCount + $commentCount) >= $this->requiredForumPosts($session);
    }

    private function requiredForumPosts(Session $session): int
    {
        return $session->required_forum_posts ?: self::DEFAULT_REQUIRED_FORUM_POSTS;
    }

    public function summaryForStudent(Course $course, string $userId): array
    {
        $sessions = $this->sessionsForCourse($course);
        $setting = $this->courseAttendanceSettingService->findByCourse($course->id);
        $totalAttendance = $sessions->filter(fn (Session $session) => $this->isSessionAttended($session, $userId))->count();

        return [
            'total_session' => $sessions->count(),
            'total_attendance' => $totalAttendance,
            'minimal_attendance' => $setting !== null ? $setting->minimal_attendance : 0,
        ];
    }
}
