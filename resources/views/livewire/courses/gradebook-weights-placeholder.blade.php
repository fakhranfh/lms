@section('title', $course->title)

<div wire:init="loadData" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <div class="space-y-space-lg animate-pulse">
        <div class="h-6 bg-surface-container rounded w-48"></div>

        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
            @for ($i = 0; $i < 4; $i++)
                <div class="p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-1/3"></div>
                    <div class="flex items-center gap-space-md">
                        <div class="flex-1 h-4 bg-surface-container rounded"></div>
                        <div class="w-16 h-6 bg-surface-container rounded-full flex-shrink-0"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>
