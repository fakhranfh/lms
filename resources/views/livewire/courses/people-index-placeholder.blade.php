@section('title', $course->title)

<div wire:init="loadData" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <div class="space-y-space-lg animate-pulse">
        <div class="h-6 bg-surface-container rounded w-40"></div>

        <div class="flex gap-space-xs border-b border-outline-variant">
            @for ($i = 0; $i < 3; $i++)
                <div class="h-8 w-24 bg-surface-container rounded-t-lg"></div>
            @endfor
        </div>

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
    </div>
</div>
