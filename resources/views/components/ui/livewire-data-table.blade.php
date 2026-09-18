<div {{ $attributes->merge(['class' => 'space-y-space-md']) }}>
    @if ($items && ($items->hasPages() || $perPage !== null))
        <div class="flex items-center gap-space-md">
            @if ($items->hasPages())
                <div class="flex-1">
                    {{ $items->links() }}
                </div>
            @endif

            @if ($perPage !== null)
                <label class="flex items-center gap-space-sm flex-shrink-0">
                    <span class="font-label-md text-label-md text-on-surface">Per page:</span>
                    <select wire:model.live="perPage" class="h-[40px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>
    @endif

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
        @unless ($items)
            <div>
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
        @else
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
                            @if ($selectable)
                                <th scope="col" class="px-space-lg py-space-md w-[1%]">
                                    {{ $selectAll ?? '' }}
                                </th>
                            @endif
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
                        @isset($bulkBar)
                            <tr x-show="selected.length > 0" x-cloak>
                                <td colspan="{{ $columnCount }}" class="px-space-lg py-space-sm bg-surface-container">
                                    {{ $bulkBar }}
                                </td>
                            </tr>
                        @endisset
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
        @endunless
    </div>

    @if ($items && $items->hasPages())
        {{ $items->links() }}
    @endif
</div>
