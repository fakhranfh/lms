<?php

namespace App\Support;

use App\Models\Course;

class CourseTabs
{
    /**
     * Builds the pre-resolved tab list (label, href, icon, active) for the
     * course-tabs Blade partial, which only binds variables and must not
     * contain any PHP logic itself.
     *
     * @return array<int, array{key: string, label: string, href: string, icon: string, active: bool}>
     */
    public static function build(Course $course, string $activeTab): array
    {
        $definitions = [
            'session' => ['label' => 'Session', 'route' => 'sessions.index', 'icon' => 'calendar_month'],
            'syllabus' => ['label' => 'Syllabus', 'route' => 'syllabus.index', 'icon' => 'menu_book'],
            'forum' => ['label' => 'Forum', 'route' => 'forum.index', 'icon' => 'forum'],
            'assessment' => ['label' => 'Assessment', 'route' => 'assessments.index', 'icon' => 'assignment'],
            'gradebook' => ['label' => 'Gradebook', 'route' => 'gradebook.index', 'icon' => 'grade'],
            'people' => ['label' => 'People', 'route' => 'people.index', 'icon' => 'groups'],
            'attendance' => ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'fact_check'],
        ];

        return collect($definitions)
            ->map(function (array $tab, string $key) use ($course, $activeTab) {
                $href = $tab['route'] === 'course-tabs.coming-soon'
                    ? route('course-tabs.coming-soon', [$course, $key])
                    : route($tab['route'], $course);

                return [
                    'key' => $key,
                    'label' => $tab['label'],
                    'href' => $href,
                    'icon' => $tab['icon'],
                    'active' => $activeTab === $key,
                ];
            })
            ->values()
            ->all();
    }
}
