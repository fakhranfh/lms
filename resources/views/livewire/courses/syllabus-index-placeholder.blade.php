@section('title', $course->title)

<div wire:init="loadSyllabus" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
        @unless ($isStudent)
            <!-- Sub-header -->
            <div class="flex items-start justify-between">
                <div class="h-5 bg-surface-container rounded w-24"></div>
                <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
            </div>
        @endunless

        <!-- Section shortcuts skeleton -->
        <div class="flex flex-wrap gap-space-sm pb-space-xs border-b border-outline-variant">
            <div class="h-9 w-28 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-24 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-56 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-32 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-24 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-44 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-20 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-32 bg-surface-container rounded-lg"></div>
            <div class="h-9 w-32 bg-surface-container rounded-lg"></div>
        </div>

        <!-- Course Description skeleton -->
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
            <div class="h-4 bg-surface-container rounded w-40"></div>
            <div class="h-3 bg-surface-container rounded w-full"></div>
            <div class="h-3 bg-surface-container rounded w-5/6"></div>
            <div class="h-3 bg-surface-container rounded w-2/3"></div>
        </div>

        <!-- Class Policies skeleton (two-column table) -->
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
            <div class="h-4 bg-surface-container rounded w-32"></div>
            <div class="border border-outline-variant rounded-lg overflow-hidden">
                <div class="grid grid-cols-2 border-b border-outline-variant">
                    <div class="p-space-lg space-y-space-xs">
                        <div class="h-3 bg-surface-container rounded w-3/4"></div>
                        <div class="h-3 bg-surface-container rounded w-2/3"></div>
                    </div>
                    <div class="p-space-lg space-y-space-xs border-l border-outline-variant">
                        <div class="h-3 bg-surface-container rounded w-3/4"></div>
                        <div class="h-3 bg-surface-container rounded w-1/2"></div>
                    </div>
                </div>
                <div class="p-space-lg space-y-space-xs">
                    <div class="h-3 bg-surface-container rounded w-5/6"></div>
                </div>
            </div>
        </div>

        <!-- Remaining sections skeleton -->
        @for ($i = 0; $i < 3; $i++)
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                <div class="h-4 bg-surface-container rounded w-40"></div>
                <div class="h-3 bg-surface-container rounded w-full"></div>
                <div class="h-3 bg-surface-container rounded w-5/6"></div>
                <div class="h-3 bg-surface-container rounded w-2/3"></div>
            </div>
        @endfor
    </div>
</div>
