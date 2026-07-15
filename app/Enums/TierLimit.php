<?php

namespace App\Enums;

enum TierLimit: string
{
    case StudentCapacityPerCourse = 'student_capacity_per_course';
    case VideoStorageGb = 'video_storage_gb';
    case LiveSessionDurationMinutes = 'live_session_duration_minutes';

    public function label(): string
    {
        return match ($this) {
            self::StudentCapacityPerCourse => 'Student Capacity Per Course',
            self::VideoStorageGb => 'Video Storage (GB)',
            self::LiveSessionDurationMinutes => 'Live Session Duration (Minutes)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::StudentCapacityPerCourse => 'Maximum number of students per course',
            self::VideoStorageGb => 'Total video storage allocation in GB',
            self::LiveSessionDurationMinutes => 'Maximum duration for live sessions (null = unlimited)',
        };
    }
}
