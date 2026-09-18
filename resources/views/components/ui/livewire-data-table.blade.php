<div {{ $attributes->merge(['class' => 'space-y-space-md']) }}>
    @if ($showPagination && $hasItems && (($isPaginated && $items->hasPages()) || $perPage !== null))
        <div class="flex items-center gap-space-md">
            @if ($isPaginated && $items->hasPages())
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
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-outline-variant bg-primary/20">
                        @if ($selectable)
                            <th scope="col" class="px-space-lg py-space-md w-[1%]">
                                {{ $selectAll ?? '' }}
                            </th>
                        @endif
                        @foreach ($columns as $column)
                            @if ($column['sortable'] ?? true)
                                <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-primary uppercase cursor-pointer select-none" wire:click="sortBy('{{ $column['key'] }}')">
                                    <span class="inline-flex items-center gap-space-2xs">
                                        {{ $column['label'] }}
                                        @if ($sort === $column['key'])
                                            <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                        @else
                                            <span class="material-symbols-outlined text-[16px] text-primary/50">unfold_more</span>
                                        @endif
                                    </span>
                                </th>
                            @else
                                <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-primary uppercase">{{ $column['label'] }}</th>
                            @endif
                        @endforeach
                        @if ($showActionsColumn)
                            <th scope="col" class="px-space-lg py-space-md text-right font-label-md text-label-md text-primary uppercase">Actions</th>
                        @endif
                    </tr>
                </thead>

                @unless ($hasItems)
                    <tbody class="divide-y divide-outline-variant">
                        @for ($row = 0; $row < 5; $row++)
                            <tr>
                                @if ($selectable)
                                    <td class="px-space-lg py-space-md w-[1%]"><div class="h-4 w-4 rounded bg-outline-variant/40 animate-pulse"></div></td>
                                @endif
                                @for ($i = 0; $i < $columnCount - ($selectable ? 1 : 0); $i++)
                                    <td class="px-space-lg py-space-md"><div class="h-4 w-full rounded bg-outline-variant/40 animate-pulse"></div></td>
                                @endfor
                            </tr>
                        @endfor
                    </tbody>
                @else
                    <tbody wire:loading.class.remove="hidden" wire:target="{{ $loadingTarget }}" class="hidden divide-y divide-outline-variant">
                        @for ($row = 0; $row < 5; $row++)
                            <tr>
                                @if ($selectable)
                                    <td class="px-space-lg py-space-md w-[1%]"><div class="h-4 w-4 rounded bg-outline-variant/40 animate-pulse"></div></td>
                                @endif
                                @for ($i = 0; $i < $columnCount - ($selectable ? 1 : 0); $i++)
                                    <td class="px-space-lg py-space-md"><div class="h-4 w-full rounded bg-outline-variant/40 animate-pulse"></div></td>
                                @endfor
                            </tr>
                        @endfor
                    </tbody>

                    <tbody wire:loading.class="hidden" wire:target="{{ $loadingTarget }}" class="divide-y divide-outline-variant">
                        @isset($bulkBar)
                            <tr x-show="selected.length > 0" x-cloak>
                                <td colspan="{{ $columnCount }}" class="px-space-lg py-space-sm bg-surface-container">
                                    {{ $bulkBar }}
                                </td>
                            </tr>
                        @endisset
                        {{ $slot }}
                    </tbody>
                @endunless
            </table>
        </div>
    </div>

    @if ($showPagination && $hasItems && $isPaginated && $items->hasPages())
        {{ $items->links() }}
    @endif
</div>
