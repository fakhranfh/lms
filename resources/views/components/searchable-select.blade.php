@props([
    'model',
    'options' => [],
    'placeholder' => 'All',
    'disabled' => false,
    'selectedLabel' => null,
])

<div
    x-data="{
        open: false,
        query: @js($selectedLabel ?? ''),
        options: @js($options),
        select(opt) {
            this.query = opt.label;
            this.open = false;
            $wire.set('{{ $model }}', opt.id);
        },
        clear() {
            this.query = '';
            this.open = false;
            $wire.set('{{ $model }}', null);
        },
        get filtered() {
            if (!this.query) return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(this.query.toLowerCase()));
        },
    }"
    @click.outside="open = false"
    class="relative"
>
    <input
        type="text"
        x-model="query"
        @focus="open = true"
        @input="open = true"
        {{ $disabled ? 'disabled' : '' }}
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="w-full h-[44px] pl-3 pr-8 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none disabled:opacity-50"
    />

    <button
        type="button"
        x-show="query"
        x-cloak
        @click="clear()"
        class="absolute inset-y-0 right-2 flex items-center justify-center text-on-surface-variant hover:text-on-surface"
    >
        <span class="material-symbols-outlined text-[18px]">close</span>
    </button>

    <div
        x-show="open && !{{ $disabled ? 'true' : 'false' }}"
        x-cloak
        class="absolute z-20 mt-1 w-full bg-surface border border-outline-variant rounded-lg shadow-lg max-h-56 overflow-y-auto"
    >
        <template x-for="opt in filtered" :key="opt.id">
            <div
                @click="select(opt)"
                class="px-space-md py-space-sm cursor-pointer hover:bg-surface-container-lowest font-body-md text-body-md text-on-surface"
                x-text="opt.label"
            ></div>
        </template>
        <div x-show="filtered.length === 0" class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">
            No results
        </div>
    </div>
</div>
