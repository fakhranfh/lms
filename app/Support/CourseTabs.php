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
            'forum' => ['label' => 'Forum', 'route' => 'course-tabs.coming-soon', 'icon' => 'forum'],
            'assessment' => ['label' => 'Assessment', 'route' => 'course-tabs.coming-soon', 'icon' => 'assignment'],
            'gradebook' => ['label' => 'Gradebook', 'route' => 'course-tabs.coming-soon', 'icon' => 'grade'],
            'people' => ['label' => 'People', 'route' => 'course-tabs.coming-soon', 'icon' => 'groups'],
            'attendance' => ['label' => 'Attendance', 'route' => 'course-tabs.coming-soon', 'icon' => 'fact_check'],
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
