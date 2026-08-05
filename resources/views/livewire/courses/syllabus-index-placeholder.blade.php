@section('title', $course->title)

<div wire:init="loadSyllabus" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
        <!-- Sub-header -->
        <div class="flex items-start justify-between">
            <div class="h-5 bg-surface-container rounded w-24"></div>
            <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
        </div>

        <!-- Sections skeleton -->
        <div class="space-y-space-md">
            @for ($i = 0; $i < 5; $i++)
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                    <div class="h-4 bg-surface-container rounded w-40"></div>
                    <div class="h-3 bg-surface-container rounded w-full"></div>
                    <div class="h-3 bg-surface-container rounded w-5/6"></div>
                    <div class="h-3 bg-surface-container rounded w-2/3"></div>
                </div>
            @endfor
        </div>
    </div>
</div>
