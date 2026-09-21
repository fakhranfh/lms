@section('title', 'Dashboard')

<div class="space-y-space-lg" @if($isStudent) wire:init="loadStudentData" @endif>

    @if($isSchoolAdmin)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <div class="flex items-center justify-between">
                    <span class="text-label-md text-secondary uppercase font-label-md">Students</span>
                    <span class="material-symbols-outlined text-[20px] text-tertiary">groups</span>
                </div>
                <p class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $totalStudents }}</p>
                <a href="{{ route('students.index') }}" class="font-body-sm text-body-sm text-primary mt-space-xs inline-block">Manage students</a>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <div class="flex items-center justify-between">
                    <span class="text-label-md text-secondary uppercase font-label-md">Teachers</span>
                    <span class="material-symbols-outlined text-[20px] text-tertiary">school</span>
                </div>
                <p class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $totalTeachers }}</p>
                <a href="{{ route('teachers.index') }}" class="font-body-sm text-body-sm text-primary mt-space-xs inline-block">Manage teachers</a>
            </div>
        </div>

        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
            <p class="font-title-md text-title-md text-on-surface mb-space-md">Quick actions</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
                <a href="{{ route('students.create') }}" class="flex items-center gap-space-sm p-space-sm rounded-md hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[20px] text-secondary">person_add</span>
                    <span class="font-body-md text-body-md text-on-surface">Add student</span>
                </a>
                <a href="{{ route('teachers.create') }}" class="flex items-center gap-space-sm p-space-sm rounded-md hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[20px] text-secondary">person_add</span>
                    <span class="font-body-md text-body-md text-on-surface">Add teacher</span>
                </a>
                <a href="{{ route('raport.index') }}" class="flex items-center gap-space-sm p-space-sm rounded-md hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[20px] text-secondary">summarize</span>
                    <span class="font-body-md text-body-md text-on-surface">View raport</span>
                </a>
            </div>
        </div>
    @endif

    @if($isTeacher)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <div class="flex items-center justify-between">
                    <span class="text-label-md text-secondary uppercase font-label-md">My Courses</span>
                    <span class="material-symbols-outlined text-[20px] text-tertiary">menu_book</span>
                </div>
                <p class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $myCourses }}</p>
                <a href="{{ route('courses.index') }}" class="font-body-sm text-body-sm text-primary mt-space-xs inline-block">View courses</a>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <div class="flex items-center justify-between">
                    <span class="text-label-md text-secondary uppercase font-label-md">Today's Sessions</span>
                    <span class="material-symbols-outlined text-[20px] text-tertiary">event</span>
                </div>
                <p class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $todaySessions }}</p>
            </div>
        </div>
    @endif

    @if($isStudent)
        @if(!$studentDataLoaded)
            <!-- Student dashboard skeleton -->
            <div class="space-y-space-lg animate-pulse">
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-40"></div>
                    @for ($i = 0; $i < 3; $i++)
                        <div class="space-y-space-xs">
                            <div class="h-3 bg-surface-container rounded w-1/3"></div>
                            <div class="h-2 bg-surface-container rounded-full w-full"></div>
                        </div>
                    @endfor
                </div>
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-32"></div>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="h-8 bg-surface-container rounded w-full"></div>
                    @endfor
                </div>
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-48"></div>
                    @for ($i = 0; $i < 3; $i++)
                        <div class="h-10 bg-surface-container rounded w-full"></div>
                    @endfor
                </div>
            </div>
        @else
            <livewire:dashboard.my-progress wire:key="dashboard-my-progress" />

            <livewire:dashboard.todo-list wire:key="dashboard-todo-list" />

            <livewire:dashboard.latest-forum-posts wire:key="dashboard-latest-forum-posts" />
        @endif
    @endif

</div>
