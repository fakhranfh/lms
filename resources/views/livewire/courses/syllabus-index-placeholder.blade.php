@section('title', $course->title)

<div wire:init="loadSyllabus" class="space-y-space-lg animate-pulse">
    @if ($isStudent)
        <!-- Teacher skeleton -->
        <div class="flex items-center gap-space-md">
            <div class="w-10 h-10 rounded-full bg-surface-container flex-shrink-0"></div>
            <div class="space-y-space-xs">
                <div class="h-3 bg-surface-container rounded w-28"></div>
                <div class="h-2 bg-surface-container rounded w-14"></div>
            </div>
        </div>
    @endif

    <!-- Course tabs -->
    <div class="border-b border-outline-variant mb-space-lg">
        <div class="flex gap-space-lg">
            <div class="h-9 w-20 bg-surface-container rounded"></div>
            <div class="h-9 w-20 bg-surface-container rounded"></div>
            <div class="h-9 w-20 bg-surface-container rounded"></div>
            <div class="h-9 w-20 bg-surface-container rounded"></div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex items-start justify-between">
        <div class="space-y-space-xs">
            <div class="h-6 bg-surface-container rounded w-48"></div>
            <div class="h-3 bg-surface-container rounded w-24"></div>
        </div>
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
