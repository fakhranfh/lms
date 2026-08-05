@section('title', 'Courses')

<div wire:init="loadCourses" class="space-y-space-lg animate-pulse">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="space-y-space-xs">
            <div class="h-6 bg-surface-container rounded w-32"></div>
            <div class="h-3 bg-surface-container rounded w-56"></div>
        </div>
        <div class="h-9 w-36 bg-surface-container rounded-lg"></div>
    </div>

    <!-- Search -->
    <div class="h-12 bg-surface-container rounded-lg w-full"></div>

    <!-- Courses Grid skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
        @for ($i = 0; $i < 6; $i++)
            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden flex flex-col">
                <div class="p-space-lg border-b border-outline-variant space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        <div class="h-5 bg-surface-container rounded-full w-16 shrink-0"></div>
                    </div>
                    <div class="space-y-space-xs">
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                        <div class="h-3 bg-surface-container rounded w-4/5"></div>
                    </div>
                    @if ($isStudent)
                        <div class="h-1.5 bg-surface-container rounded-full w-full"></div>
                    @endif
                </div>
                <div class="px-space-lg py-space-md space-y-space-sm flex-1">
                    <div class="h-3 bg-surface-container rounded w-3/4"></div>
                    <div class="h-3 bg-surface-container rounded w-2/3"></div>
                    <div class="h-3 bg-surface-container rounded w-1/2"></div>
                </div>
                <div class="px-space-lg py-space-md bg-surface-container/50 border-t border-outline-variant flex items-center justify-between">
                    <div class="h-3 bg-surface-container rounded w-20"></div>
                    <div class="flex gap-space-xs">
                        <div class="h-8 w-8 bg-surface-container rounded"></div>
                        <div class="h-8 w-8 bg-surface-container rounded"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>
