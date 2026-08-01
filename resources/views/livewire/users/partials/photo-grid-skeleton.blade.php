<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-space-md">
    @for ($i = 0; $i < 10; $i++)
        <div class="flex flex-col items-center gap-space-xs p-space-md bg-surface border border-outline-variant rounded-lg">
            <div class="w-20 h-20 rounded-full bg-outline-variant/60 animate-pulse"></div>
            <div class="h-3 w-16 rounded bg-outline-variant/60 animate-pulse"></div>
            <div class="h-3 w-12 rounded bg-outline-variant/40 animate-pulse"></div>
        </div>
    @endfor
</div>
