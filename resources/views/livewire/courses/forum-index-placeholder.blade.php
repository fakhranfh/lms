@section('title', $course->title)

<div wire:init="loadForum" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
        <!-- Session switcher skeleton -->
        <div class="flex flex-wrap gap-space-sm pb-space-xs border-b border-outline-variant">
            <div class="h-9 w-24 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-32 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-32 bg-surface-container rounded-lg"></div>
        </div>

        <!-- Thread list skeleton -->
        @for ($i = 0; $i < 4; $i++)
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                <div class="h-4 bg-surface-container rounded w-1/3"></div>
                <div class="h-3 bg-surface-container rounded w-full"></div>
                <div class="h-3 bg-surface-container rounded w-2/3"></div>
            </div>
        @endfor
    </div>
</div>
