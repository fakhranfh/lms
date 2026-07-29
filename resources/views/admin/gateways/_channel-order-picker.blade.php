{{--
    Reorderable payment-channel picker used by both the create and edit
    gateway forms. Channel order here is what customers see (top-to-bottom)
    on the school checkout page, so admins need to control it directly
    rather than relying on a fixed checkbox grid.
--}}
<div
    x-data="channelOrderPicker({
        all: @js(collect(\App\Enums\XenditChannel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->values()),
        initialEnabled: @js($initialEnabledChannels ?? []),
    })"
>
    <span class="block text-body-md font-medium text-on-surface mb-2">
        Enabled Payment Channels <span class="text-error">*</span>
    </span>
    <p class="text-body-sm text-on-surface-variant mb-3">Order here is the order customers see on the checkout page.</p>

    <div class="space-y-2 mb-4">
        <template x-for="(channel, index) in enabled" :key="channel.value">
            <div
                draggable="true"
                @dragstart="dragIndex = index; $el.classList.add('opacity-50')"
                @dragend="dragIndex = null; $el.classList.remove('opacity-50')"
                @dragover.prevent
                @dragenter.prevent="dropIndex = index"
                @drop.prevent="reorder(dragIndex, index)"
                class="flex items-center gap-3 px-3 py-2 border border-outline rounded-lg bg-surface-container cursor-move transition-transform"
                :class="{ 'border-primary border-2': dropIndex === index && dragIndex !== index }"
            >
                <span class="text-on-surface-variant select-none" aria-hidden="true">&#9776;</span>
                <span class="text-body-sm text-on-surface-variant w-5" x-text="index + 1"></span>
                <span class="text-body-sm text-on-surface flex-1" x-text="channel.label"></span>
                <input type="hidden" name="enabled_channels[]" :value="channel.value">
                <button type="button" class="px-2 py-1 text-on-surface-variant hover:text-on-surface disabled:opacity-30" :disabled="index === 0" @click="moveUp(index)" aria-label="Move up">&uarr;</button>
                <button type="button" class="px-2 py-1 text-on-surface-variant hover:text-on-surface disabled:opacity-30" :disabled="index === enabled.length - 1" @click="moveDown(index)" aria-label="Move down">&darr;</button>
                <button type="button" class="px-2 py-1 text-error hover:opacity-75" @click="disable(channel)" aria-label="Remove">&times;</button>
            </div>
        </template>
        <p x-show="enabled.length === 0" class="text-body-sm text-on-surface-variant">No channels enabled yet — add one below.</p>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <template x-for="channel in available" :key="channel.value">
            <button type="button" class="flex items-center justify-between gap-2 px-3 py-2 border border-outline rounded-lg text-body-sm text-on-surface hover:bg-surface-container transition" @click="enable(channel)">
                <span x-text="channel.label"></span>
                <span class="text-primary">+</span>
            </button>
        </template>
    </div>

    @error('enabled_channels')
        <p class="text-error text-body-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<script>
    function channelOrderPicker({ all, initialEnabled }) {
        return {
            enabled: initialEnabled
                .map((value) => all.find((c) => c.value === value))
                .filter(Boolean),
            dragIndex: null,
            dropIndex: null,

            get available() {
                const enabledValues = this.enabled.map((c) => c.value);

                return all.filter((c) => !enabledValues.includes(c.value));
            },

            enable(channel) {
                this.enabled.push(channel);
            },

            disable(channel) {
                this.enabled = this.enabled.filter((c) => c.value !== channel.value);
            },

            moveUp(index) {
                if (index === 0) return;
                [this.enabled[index - 1], this.enabled[index]] = [this.enabled[index], this.enabled[index - 1]];
            },

            moveDown(index) {
                if (index === this.enabled.length - 1) return;
                [this.enabled[index + 1], this.enabled[index]] = [this.enabled[index], this.enabled[index + 1]];
            },

            reorder(fromIndex, toIndex) {
                this.dropIndex = null;

                if (fromIndex === null || fromIndex === toIndex) return;

                const [moved] = this.enabled.splice(fromIndex, 1);
                this.enabled.splice(toIndex, 0, moved);
            },
        };
    }
</script>
