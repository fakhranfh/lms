@props([
    'columns' => [],
    'items' => null,
    'sort' => null,
    'direction' => 'asc',
    'loadingTarget' => 'search,applyFilters,resetFilters,sortBy,perPage',
])

@php
    $columnCount = count($columns) + 1;
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface border border-outline-variant rounded-lg overflow-hidden']) }}>
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
            <thead>
                <tr class="border-b border-outline-variant bg-surface-container-lowest">
                    @foreach ($columns as $column)
                        @if ($column['sortable'] ?? true)
                            <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase cursor-pointer select-none" wire:click="sortBy('{{ $column['key'] }}')">
                                <span class="inline-flex items-center gap-space-2xs">
                                    {{ $column['label'] }}
                                    @if ($sort === $column['key'])
                                        <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    @else
                                        <span class="material-symbols-outlined text-[16px] text-secondary/50">unfold_more</span>
                                    @endif
                                </span>
                            </th>
                        @else
                            <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">{{ $column['label'] }}</th>
                        @endif
                    @endforeach
                    <th scope="col" class="px-space-lg py-space-md text-right font-label-md text-label-md text-secondary uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if ($items)
        <div class="px-space-lg py-space-md border-t border-outline-variant">
            {{ $items->links() }}
        </div>
    @endif
</div>
