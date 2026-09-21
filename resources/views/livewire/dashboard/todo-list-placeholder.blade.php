<div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
    <div class="flex items-center justify-between mb-space-md flex-wrap gap-space-sm">
        <h2 class="text-title-md font-title-md font-bold text-on-surface">To-Do</h2>
    </div>

    <div class="animate-pulse divide-y divide-outline-variant">
        @for ($i = 0; $i < 4; $i++)
            <div class="py-space-sm flex items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-sm min-w-0 flex-1">
                    <div class="h-[18px] w-[18px] bg-surface-container rounded-full shrink-0"></div>
                    <div class="min-w-0 flex-1 space-y-space-xs">
                        <div class="h-3.5 bg-surface-container rounded w-2/3"></div>
                        <div class="h-3 bg-surface-container rounded w-1/3"></div>
                    </div>
                </div>
                <div class="h-3 bg-surface-container rounded w-16 shrink-0"></div>
            </div>
        @endfor
    </div>
</div>
