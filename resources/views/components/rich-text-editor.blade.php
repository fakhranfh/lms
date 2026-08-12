@props(['id', 'wireModel', 'value' => '', 'disabled' => false])

<div
    wire:ignore
    x-data="richTextEditor(@js($value), '{{ $wireModel }}', '{{ $id }}', @js((bool) $disabled))"
    x-on:rich-text-cleared.window="if ($event.detail.id === id) { clear(); }"
    x-on:rich-text-disabled-changed.window="if ($event.detail.id === id) { disabled = $event.detail.disabled; }"
    class="bg-surface border border-outline rounded-lg"
    :class="disabled && 'opacity-60'"
>
    <div class="flex flex-wrap items-center gap-1 rounded-t-lg border-b border-outline-variant bg-surface-container-lowest px-2 py-1">
        <button type="button" @click="exec('bold')" :disabled="disabled" :class="active.bold && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_bold</span>
        </button>
        <button type="button" @click="exec('italic')" :disabled="disabled" :class="active.italic && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_italic</span>
        </button>
        <button type="button" @click="exec('underline')" :disabled="disabled" :class="active.underline && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_underlined</span>
        </button>
        <button type="button" @click="insertLink()" :disabled="disabled" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">link</span>
        </button>

        <span class="w-px h-5 bg-outline-variant mx-1"></span>

        <button type="button" @click="toggleBlock('h1')" :disabled="disabled" :class="active.h1 && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_h1</span>
        </button>
        <button type="button" @click="toggleBlock('h2')" :disabled="disabled" :class="active.h2 && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_h2</span>
        </button>
        <button type="button" @click="toggleBlock('blockquote')" :disabled="disabled" :class="active.blockquote && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_quote</span>
        </button>

        <span class="w-px h-5 bg-outline-variant mx-1"></span>

        <button type="button" @click="exec('insertUnorderedList')" :disabled="disabled" :class="active.ul && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
        </button>
        <button type="button" @click="exec('insertOrderedList')" :disabled="disabled" :class="active.ol && 'bg-primary/10 text-primary'" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
        </button>

        <span class="w-px h-5 bg-outline-variant mx-1"></span>

        <div class="relative" x-data="{ tableOpen: false, hoverRow: 0, hoverCol: 0, manualRows: 2, manualCols: 2 }">
            <button type="button" @click="tableOpen = !tableOpen" :disabled="disabled" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined text-[18px]">table</span>
            </button>

            <div
                x-show="tableOpen"
                x-cloak
                @click.outside="tableOpen = false"
                class="absolute z-10 mt-1 w-56 rounded-lg border border-outline bg-surface p-3 shadow-lg"
            >
                <p class="mb-2 text-body-xs text-on-surface-variant" x-text="(hoverRow || manualRows) + ' x ' + (hoverCol || manualCols)"></p>

                <div class="mb-3 grid grid-cols-8 gap-0.5" @mouseleave="hoverRow = 0; hoverCol = 0">
                    <template x-for="r in 8" :key="'row-'+r">
                        <template x-for="c in 8" :key="'col-'+c">
                            <div
                                class="h-4 w-4 border"
                                :class="(r <= hoverRow && c <= hoverCol) ? 'bg-primary border-primary' : 'border-outline-variant'"
                                @mouseenter="hoverRow = r; hoverCol = c"
                                @click="insertTable(r, c); tableOpen = false; hoverRow = 0; hoverCol = 0"
                            ></div>
                        </template>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <input type="number" min="1" max="20" step="1" required x-model.number="manualRows" @input="manualRows = Math.max(1, Math.min(20, Math.trunc(manualRows) || 1))" class="w-14 rounded border border-outline px-1 py-0.5 text-body-xs">
                    <span class="text-body-xs text-on-surface-variant">x</span>
                    <input type="number" min="1" max="20" step="1" required x-model.number="manualCols" @input="manualCols = Math.max(1, Math.min(20, Math.trunc(manualCols) || 1))" class="w-14 rounded border border-outline px-1 py-0.5 text-body-xs">
                    <button
                        type="button"
                        @click="insertTable(manualRows, manualCols); tableOpen = false"
                        :disabled="!manualRows || manualRows < 1 || !manualCols || manualCols < 1"
                        class="ml-auto text-body-xs font-medium text-primary hover:underline disabled:opacity-50 disabled:cursor-not-allowed disabled:no-underline"
                    >
                        Buat
                    </button>
                </div>
            </div>
        </div>

        <span class="w-px h-5 bg-outline-variant mx-1"></span>

        <button type="button" @click="triggerFilePicker()" :disabled="disabled || uploading" class="p-1.5 rounded hover:bg-surface-container disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]">attach_file</span>
        </button>
        <input type="file" x-ref="fileInput" accept="image/*,.pdf,.zip" class="hidden" :disabled="disabled" @change="uploadFile($event)">
    </div>

    <div
        id="rte-{{ $id }}"
        x-ref="editor"
        :contenteditable="!disabled"
        class="rte-content rounded-b-lg"
        :class="disabled && 'cursor-not-allowed select-none'"
        @input="onInput()"
        @keyup="updateActiveStates()"
        @mouseup="updateActiveStates()"
        @paste="onPaste($event)"
    ></div>
</div>
