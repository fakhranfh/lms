@section('title', $course->title)

<div wire:init="loadData" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="flex items-start justify-between animate-pulse">
        <div class="h-8 bg-surface-container rounded w-40"></div>

        @if ($canManage)
            <div class="flex-shrink-0 flex items-center gap-space-sm">
                @if ($isLocalEnv)
                    <div class="h-10 bg-surface-container rounded-lg w-44"></div>
                    <div class="h-10 bg-surface-container rounded-lg w-36"></div>
                @endif
                <div class="h-10 bg-surface-container rounded-lg w-44"></div>
                <div class="h-10 bg-surface-container rounded-lg w-40"></div>
            </div>
        @endif
    </div>

    <div class="space-y-space-lg animate-pulse">
        @if ($isStudent)
            <div class="h-24 bg-surface-container rounded-lg"></div>

            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
                @for ($i = 0; $i < 4; $i++)
                    <div class="p-space-lg flex items-center gap-space-md">
                        <div class="flex-1 space-y-space-xs">
                            <div class="h-4 bg-surface-container rounded w-1/3"></div>
                            <div class="h-3 bg-surface-container rounded w-1/4"></div>
                        </div>
                        <div class="w-20 h-6 bg-surface-container rounded-full flex-shrink-0"></div>
                    </div>
                @endfor
            </div>
        @else
            <div class="flex items-center gap-space-sm flex-wrap">
                <div class="h-4 bg-surface-container rounded w-10"></div>
                <div class="flex items-center gap-space-xs">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="w-7 h-7 bg-surface-container rounded-full"></div>
                    @endfor
                </div>
            </div>

            <div class="space-y-space-md">
                <div class="h-9 bg-surface-container rounded-lg w-56"></div>

                <div class="flex items-center justify-between gap-space-md flex-wrap">
                    <div class="h-4 bg-surface-container rounded w-40"></div>
                    <div class="flex items-center gap-space-md">
                        <div class="h-9 bg-surface-container rounded-lg w-48"></div>
                        <div class="h-9 bg-surface-container rounded-lg w-24"></div>
                    </div>
                </div>
            </div>

            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <x-ui.person-grid-skeleton :rows="9" />
            </div>
        @endif
    </div>
</div>
