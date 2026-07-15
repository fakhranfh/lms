@props(['id' => 'deleteModal', 'title' => 'Delete Confirmation', 'message' => 'This action cannot be undone.', 'resourceName' => 'item'])

<div
    x-data="{ open: false }"
    @delete-modal-open.window="open = true"
    @delete-modal-close.window="open = false"
    class="fixed inset-0 z-50"
>
    <!-- Overlay -->
    <div
        x-show="open"
        @click="open = false"
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Modal -->
    <div
        x-show="open"
        class="fixed inset-0 flex items-center justify-center p-4"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
            <div class="p-space-lg space-y-space-lg">
                <!-- Icon -->
                <div class="flex justify-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                        <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                    </div>
                </div>

                <!-- Title & Message -->
                <div class="text-center space-y-space-sm">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $title }}</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $message }}</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-space-md pt-space-md">
                    <button
                        @click="open = false"
                        type="button"
                        class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="open = false; window.dispatchEvent(new CustomEvent('confirm-delete'))"
                        type="button"
                        class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
