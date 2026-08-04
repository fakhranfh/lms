<?php

namespace App\Enums;

enum AttendanceRequirementType: string
{
    case ManualCheckin = 'manual_checkin';
    case ForumCompleted = 'forum_completed';
    case ClassDurationCompleted = 'class_duration_completed';
}
