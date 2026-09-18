<div {{ $attributes->merge(['class' => $containerClass]) }}>
    @if ($loadingTarget)
        <div wire:loading.block wire:target="{{ $loadingTarget }}">
            <div class="grid border-b border-outline-variant bg-surface-container-lowest" style="grid-template-columns: repeat({{ $columnCount }}, minmax(0, 1fr));">
                @for ($i = 0; $i < $columnCount; $i++)
                    <div class="px-space-lg py-space-md"><div class="h-4 w-full rounded bg-outline-variant/60 animate-pulse"></div></div>
                @endfor
            </div>
            @for ($row = 0; $row < 5; $row++)
                <div class="grid border-b border-outline-variant last:border-0" style="grid-template-columns: repeat({{ $columnCount }}, minmax(0, 1fr));">
                    @for ($i = 0; $i < $columnCount; $i++)
                        <div class="px-space-lg py-space-md"><div class="h-4 w-full rounded bg-outline-variant/40 animate-pulse"></div></div>
                    @endfor
                </div>
            @endfor
        </div>

        <div wire:loading.remove wire:target="{{ $loadingTarget }}" class="overflow-x-auto">
            <table class="w-full">
                {{ $head }}
                {{ $slot }}
            </table>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                {{ $head }}
                {{ $slot }}
            </table>
        </div>
    @endif
</div>
