@section('title', $course->title)

<div wire:init="loadAssessments" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
        <div class="flex items-start justify-between">
            <div class="space-y-space-xs">
                <div class="h-6 bg-surface-container rounded w-40"></div>
            </div>
            <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
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
