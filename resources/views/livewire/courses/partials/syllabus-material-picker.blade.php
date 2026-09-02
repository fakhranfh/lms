<div
    x-data="materialPicker({
        property: 'selectedMaterialIds.{{ $section }}',
        initialSelected: @js($selectedMediaItemsBySection[$section]->map(fn ($item) => ['id' => (string) $item->id, 'title' => $item->title, 'type' => $item->type->value])->values()),
    })"
    x-on:syllabus-material-set.window="if ($event.detail.section === '{{ $section }}') { setItems($event.detail.items); }"
    class="mt-space-sm"
>
    <button
        type="button"
        @click="open = true"
        class="w-full flex items-center justify-center gap-space-sm px-space-lg py-space-sm border border-dashed border-outline rounded-lg text-body-sm text-primary font-medium hover:bg-surface-container/50 transition-colors"
    >
        <span class="material-symbols-outlined text-[18px]">perm_media</span>
        Attach Material to {{ $label }}
    </button>

    <div class="mt-space-sm space-y-space-xs" x-show="selectedItems.length > 0">
        <template x-for="item in selectedItems" :key="item.id">
            <div class="flex items-center gap-space-md p-space-sm border border-outline-variant rounded-lg">
                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">description</span>
                <span class="text-body-sm text-on-surface flex-1" x-text="item.title"></span>
                <span class="text-body-xs text-on-surface-variant" x-text="item.type"></span>
                <button type="button" @click="remove(item.id)" class="p-1 hover:bg-surface-container rounded transition text-error">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </template>
    </div>

    <div
        x-show="open"
        x-cloak
        @click.self="open = false"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
    >
        <div class="bg-surface border border-outline-variant rounded-lg max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden" @click.stop>
            <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                <h3 class="font-label-lg text-label-lg text-on-surface">Choose Material — {{ $label }}</h3>
                <button type="button" @click="open = false" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="px-space-lg py-space-md border-b border-outline-variant">
                <x-ui.search-input wire-model="materialSearch" placeholder="Search media..." compact />
            </div>

            <div class="flex-1 overflow-y-auto p-space-lg">
                <div
                    wire:loading.delay.class.remove="hidden"
                    wire:target="materialSearch"
                    class="hidden grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-space-md animate-pulse"
                >
                    @for ($i = 0; $i < 12; $i++)
                        <div class="flex flex-col items-center gap-space-xs p-space-sm">
                            <div class="w-full aspect-square rounded-md bg-surface-container"></div>
                            <div class="h-2 bg-surface-container rounded w-3/4"></div>
                        </div>
                    @endfor
                </div>

                <div wire:loading.remove wire:target="materialSearch">
                    @if ($mediaItems->isEmpty())
                        <p class="p-space-lg text-center text-body-sm text-on-surface-variant">No media found.</p>
                    @else
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-space-md">
                            @foreach ($mediaItems as $item)
                                <label
                                    wire:key="explorer-material-{{ $section }}-{{ $item->id }}"
                                    @click.prevent="toggle({ id: '{{ $item->id }}', title: @js($item->title), type: '{{ $item->type->value }}' })"
                                    class="flex flex-col items-center gap-space-xs p-space-sm rounded-lg border cursor-pointer hover:bg-surface-container/50 transition-colors"
                                    :class="selectedIds.includes('{{ $item->id }}') ? 'border-primary bg-primary/5' : 'border-transparent'"
                                >
                                    <input type="checkbox" :checked="selectedIds.includes('{{ $item->id }}')" class="sr-only" />
                                    <div class="w-full aspect-square rounded-md bg-surface-container flex items-center justify-center">
                                        <span class="material-symbols-outlined text-on-surface-variant text-[28px]">description</span>
                                    </div>
                                    <p class="w-full text-body-xs text-on-surface text-center line-clamp-2 break-words leading-tight">
                                        {{ $item->title }}
                                    </p>
                                    <span class="text-body-xs text-on-surface-variant">{{ $item->type->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="px-space-lg py-space-md border-t border-outline-variant flex items-center justify-between">
                <p class="text-body-sm text-on-surface-variant"><span x-text="selectedItems.length"></span> selected</p>
                <button
                    type="button"
                    @click="open = false"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                >
                    Done
                </button>
            </div>
        </div>
    </div>
</div>
