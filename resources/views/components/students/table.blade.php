@props([
    'students',
    'studentsLoaded' => true,
    'sort' => 'name',
    'direction' => 'asc',
])

<div x-data="{
        deleteId: null, showDeleteModal: false,
        selected: [],
        selectAllMatching: @entangle('selectAllMatching'),
        matchingCount: @entangle('matchingCount'),
        pageIds: @entangle('pageIds'),
        csrfToken() {
            return document.querySelector('meta[name=csrf-token]').content;
        },
        init() {
            fetch('{{ route('students.selection.show') }}', { headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(data => { this.selected = data.selected ?? []; })
                .catch(() => {});
        },
        get allOnPageSelected() {
            if (this.selectAllMatching) {
                return true;
            }

            return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id));
        },
        toggleSelectAll(checked) {
            if (this.selectAllMatching) {
                if (! checked) {
                    this.clearSelection();
                }

                return;
            }

            const ids = this.pageIds;
            const previous = this.selected;
            this.selected = checked
                ? [...new Set([...this.selected, ...ids])]
                : this.selected.filter(id => ! ids.includes(id));

            fetch('{{ route('students.selection.update-many') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ ids, checked }),
            }).catch(() => { this.selected = previous; });
        },
        toggleStudent(id, checked) {
            if (this.selectAllMatching) {
                this.selectAllMatching = false;

                if (checked) {
                    this.selected = [id];
                    this.persistSelection(this.selected);

                    return;
                }

                // Unchecking one row while 'all matching' was active means
                // 'everything except this one' — fetch the full matching id
                // list rather than losing the rest of the selection.
                $wire.call('matchingIds').then(ids => {
                    this.selected = ids.filter(existing => existing !== id);
                    this.persistSelection(this.selected);
                });

                return;
            }

            const previous = this.selected;
            this.selected = checked
                ? [...new Set([...this.selected, id])]
                : this.selected.filter(existing => existing !== id);

            fetch('{{ route('students.selection.update') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ id, checked }),
            }).catch(() => { this.selected = previous; });
        },
        /**
         * Replaces the persisted selection wholesale — used when the exact
         * set of ids is already known client-side (e.g. dropping out of
         * 'select all matching') rather than toggling one id at a time.
         */
        persistSelection(ids) {
            fetch('{{ route('students.selection.clear') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
            }).then(() => {
                if (ids.length === 0) {
                    return;
                }

                return fetch('{{ route('students.selection.update-many') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ ids, checked: true }),
                });
            }).catch(() => {});
        },
        clearSelection() {
            this.selected = [];
            this.selectAllMatching = false;

            fetch('{{ route('students.selection.clear') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
            }).catch(() => {});
        },
    }"
>
    <div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
        @unless ($studentsLoaded)
            @include('components.students.partials.table-skeleton')
        @else
            <div wire:loading.block wire:target="search,sortBy,perPage,gotoPage,previousPage,nextPage">
                @include('components.students.partials.table-skeleton')
            </div>

            <div wire:loading.remove wire:target="search,sortBy,perPage,gotoPage,previousPage,nextPage" class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-container border-b border-outline-variant">
                        <tr>
                            @can('students.delete')
                                <th class="px-space-lg py-space-md w-[1%]">
                                    <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll($event.target.checked)" aria-label="Select all students on this page">
                                </th>
                            @endcan
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('name')">
                                <span class="inline-flex items-center gap-space-2xs">
                                    Name
                                    @if ($sort === 'name')
                                        <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    @else
                                        <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                                    @endif
                                </span>
                            </th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('email')">
                                <span class="inline-flex items-center gap-space-2xs">
                                    Email
                                    @if ($sort === 'email')
                                        <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    @else
                                        <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                                    @endif
                                </span>
                            </th>
                            <th class="px-space-lg py-space-md text-right font-label-md text-label-md text-on-surface">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @can('students.delete')
                            <tr x-show="selected.length > 0" x-cloak>
                                <td colspan="4" class="px-space-lg py-space-sm bg-surface-container">
                                    <div class="flex items-center justify-between gap-space-md flex-wrap">
                                        <div class="flex items-center gap-space-md flex-wrap">
                                            <template x-if="!selectAllMatching">
                                                <p class="font-label-md text-label-md text-on-surface"><span x-text="selected.length"></span> selected</p>
                                            </template>

                                            <template x-if="!selectAllMatching && allOnPageSelected && matchingCount > selected.length">
                                                <button type="button" @click="selectAllMatching = true" class="font-label-sm text-label-sm text-primary hover:underline">
                                                    <span x-text="`Select all ${matchingCount} students`"></span>
                                                </button>
                                            </template>

                                            <template x-if="selectAllMatching">
                                                <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                    <span x-text="`All ${matchingCount} students selected.`"></span>
                                                    <button type="button" @click="clearSelection()" class="text-primary hover:underline">Clear selection</button>
                                                </p>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-space-sm">
                                            <button
                                                type="button"
                                                @click="clearSelection()"
                                                class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface transition"
                                            >
                                                Clear
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteId = null; showDeleteModal = true"
                                                class="px-space-md py-space-xs bg-error text-on-error rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity inline-flex items-center gap-space-xs"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                                Delete Selected
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endcan
                        @forelse ($students as $student)
                            <x-students.partials.table-row :student="$student" />
                        @empty
                            <tr>
                                <td colspan="4" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                                    No students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endunless
    </div>

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showDeleteModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
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
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface" x-text="deleteId === null ? 'Delete Selected Students' : 'Delete Student'"></h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="deleteId === null ? (selectAllMatching ? `Are you sure you want to delete all ${matchingCount} students matching your search? This action cannot be undone.` : `Are you sure you want to delete ${selected.length} selected student(s)? This action cannot be undone.`) : 'Are you sure you want to delete this student? This action cannot be undone.'"></p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            @click="showDeleteModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="showDeleteModal = false; if (deleteId === null) { const deletion = selectAllMatching ? $wire.call('destroyAllMatching') : $wire.call('destroySelected', selected); deletion.then(() => clearSelection()) } else { $wire.call('destroy', deleteId) }"
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
</div>
