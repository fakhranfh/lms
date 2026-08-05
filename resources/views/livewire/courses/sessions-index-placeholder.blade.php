@section('title', $course->title)

<div wire:init="loadSessions" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
    @if ($isStudent)
        <!-- Session tabs -->
        <div class="flex gap-space-xs pb-space-xs border-b border-outline-variant">
            <div class="h-9 w-32 bg-surface-container rounded-t-lg"></div>
            <div class="h-9 w-24 bg-surface-container rounded-t-lg"></div>
            <div class="h-9 w-24 bg-surface-container rounded-t-lg"></div>
        </div>

        <!-- Session Detail skeleton -->
        <div class="w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <div class="flex items-start justify-between gap-space-md">
                <div class="h-6 bg-surface-container rounded w-1/3"></div>
                <div class="flex flex-col items-stretch gap-space-sm flex-shrink-0">
                    <div class="h-9 w-40 bg-surface-container rounded-lg"></div>
                    <div class="h-9 w-40 bg-surface-container rounded-lg"></div>
                </div>
            </div>

            <div class="space-y-space-sm">
                <div class="h-3 bg-surface-container rounded w-28"></div>
                <div class="flex items-start gap-space-sm">
                    <div class="w-1.5 h-1.5 rounded-full bg-surface-container mt-2 flex-shrink-0"></div>
                    <div class="h-3 bg-surface-container rounded w-full"></div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-space-md pt-space-md border-t border-outline-variant">
                <div class="space-y-space-xs">
                    <div class="h-2 bg-surface-container rounded w-10"></div>
                    <div class="h-3 bg-surface-container rounded w-full"></div>
                </div>
                <div class="space-y-space-xs">
                    <div class="h-2 bg-surface-container rounded w-8"></div>
                    <div class="h-3 bg-surface-container rounded w-full"></div>
                </div>
                <div class="space-y-space-xs">
                    <div class="h-2 bg-surface-container rounded w-20"></div>
                    <div class="h-3 bg-surface-container rounded w-full"></div>
                </div>
            </div>
        </div>

        <!-- Learning Progress skeleton -->
        <div class="w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <div class="space-y-space-md">
                <div class="flex items-center gap-space-xs">
                    <div class="h-4 bg-surface-container rounded w-32"></div>
                    <div class="h-4 w-4 bg-surface-container rounded"></div>
                    <div class="ml-auto h-4 bg-surface-container rounded w-10"></div>
                </div>

                <div class="w-full h-2 bg-surface-container rounded-full"></div>

                <div class="flex flex-wrap gap-space-sm">
                    <div class="h-7 bg-surface-container rounded-full w-36"></div>
                    <div class="h-7 bg-surface-container rounded-full w-28"></div>
                    <div class="h-7 bg-surface-container rounded-full w-24"></div>
                    <div class="h-7 bg-surface-container rounded-full w-20"></div>
                </div>
            </div>

            <div class="flex flex-col items-center gap-space-lg py-space-lg">
                <div class="w-40 h-40 rounded-full bg-surface-container"></div>
                <div class="h-12 w-48 bg-surface-container rounded-lg"></div>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="space-y-space-xs">
                <div class="h-6 bg-surface-container rounded w-40"></div>
                <div class="h-3 bg-surface-container rounded w-24"></div>
            </div>
            <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
        </div>

        <!-- Accordion rows skeleton -->
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
            @for ($i = 0; $i < 4; $i++)
                <div class="p-space-lg flex items-center gap-space-md">
                    <div class="w-9 h-9 bg-surface-container rounded flex-shrink-0"></div>
                    <div class="flex-1 space-y-space-xs">
                        <div class="h-4 bg-surface-container rounded w-1/3"></div>
                        <div class="h-3 bg-surface-container rounded w-1/4"></div>
                    </div>
                    <div class="w-9 h-9 bg-surface-container rounded flex-shrink-0"></div>
                    <div class="w-9 h-9 bg-surface-container rounded flex-shrink-0"></div>
                </div>
            @endfor
        </div>
    @endif
    </div>
</div>
