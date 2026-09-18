<div class="grid border-b border-outline-variant bg-surface-container" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
    @for ($i = 0; $i < 3; $i++)
        <div class="px-space-lg py-space-md"><div class="h-4 w-24 rounded bg-outline-variant/60 animate-pulse"></div></div>
    @endfor
</div>
@for ($row = 0; $row < 5; $row++)
    <div class="grid border-b border-outline-variant last:border-0" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        @for ($i = 0; $i < 3; $i++)
            <div class="px-space-lg py-space-md"><div class="h-4 w-full max-w-32 rounded bg-outline-variant/40 animate-pulse"></div></div>
        @endfor
    </div>
@endfor
