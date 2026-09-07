{{--
    Molecule: the "N selected / Clear / Delete Selected" bar shown above a
    type group's table. Rendered once per table (rather than once at the
    top of the page) so it stays in view without scrolling back up, no
    matter which group the user is working in. Relies on the ancestor
    x-data scope in assessment-index.blade.php for `selectedIds`,
    `deleteMode`, `showDeleteModal` and `bulkDeleting`.
--}}
<div x-show="selectedIds.length > 0" x-cloak class="flex items-center justify-between px-space-lg py-space-sm bg-surface-container border border-outline-variant rounded-lg mb-space-sm">
    <p class="font-label-md text-label-md text-on-surface"><span x-text="selectedIds.length"></span> selected</p>
    <div class="flex items-center gap-space-sm">
        <button
            type="button"
            @click="selectedIds = []"
            class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface transition"
        >
            Clear
        </button>
        <button
            type="button"
            :disabled="bulkDeleting"
            @click="deleteMode = 'bulk'; showDeleteModal = true"
            class="px-space-md py-space-xs bg-error text-on-error rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity inline-flex items-center gap-space-xs disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span x-show="!bulkDeleting" class="material-symbols-outlined text-[16px]">delete</span>
            <span x-show="bulkDeleting" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
            <span x-text="bulkDeleting ? 'Deleting...' : 'Delete Selected'"></span>
        </button>
    </div>
</div>
