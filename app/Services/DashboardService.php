<?php

namespace App\Services;

use App\Enums\RoleInCourse;
use App\Models\Course;
use App\Models\Session;

class DashboardService
{
    public function countTeacherCourses(string $userId): int
    {
        return Course::whereHas(
            'people',
            fn ($query) => $query->where('user_id', $userId)->where('role_in_course', RoleInCourse::Teacher)
        )->count();
    }

    public function countTeacherSessionsToday(string $userId): int
    {
        return Session::whereHas(
            'course.people',
            fn ($query) => $query->where('user_id', $userId)->where('role_in_course', RoleInCourse::Teacher)
        )->whereDate('date_start', today())->count();
    }
}
