@php
    $tabs = [
        'session' => ['label' => 'Session', 'route' => 'sessions.index', 'icon' => 'calendar_month'],
        'syllabus' => ['label' => 'Syllabus', 'route' => 'course-tabs.coming-soon', 'icon' => 'menu_book'],
        'forum' => ['label' => 'Forum', 'route' => 'course-tabs.coming-soon', 'icon' => 'forum'],
        'assessment' => ['label' => 'Assessment', 'route' => 'course-tabs.coming-soon', 'icon' => 'assignment'],
        'gradebook' => ['label' => 'Gradebook', 'route' => 'course-tabs.coming-soon', 'icon' => 'grade'],
        'people' => ['label' => 'People', 'route' => 'course-tabs.coming-soon', 'icon' => 'groups'],
        'attendance' => ['label' => 'Attendance', 'route' => 'course-tabs.coming-soon', 'icon' => 'fact_check'],
    ];
@endphp

<div class="border-b border-outline-variant mb-space-lg">
    <nav class="flex gap-space-lg overflow-x-auto">
        @foreach ($tabs as $key => $tab)
            @php
                $href = $tab['route'] === 'sessions.index'
                    ? route('sessions.index', $course)
                    : route('course-tabs.coming-soon', [$course, $key]);
                $isActive = $activeTab === $key;
            @endphp
            <a
                href="{{ $href }}"
                class="flex items-center gap-space-xs px-space-sm py-space-md border-b-2 font-label-md text-label-md whitespace-nowrap transition-colors {{ $isActive ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}"
            >
                <span class="material-symbols-outlined text-[18px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
