@props([
    'teachers',
    'teachersLoaded' => true,
    'sort' => 'name',
    'direction' => 'asc',
])

<div x-data="{
        deleteId: null, showDeleteModal: false,
        deletingIds: [],
        selected: [],
        selectAllMatching: @entangle('selectAllMatching'),
        matchingCount: @entangle('matchingCount'),
        pageIds: @entangle('pageIds'),
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
            this.selected = checked
                ? [...new Set([...this.selected, ...ids])]
                : this.selected.filter(id => ! ids.includes(id));
        },
        toggleTeacher(id, checked) {
            if (this.selectAllMatching) {
                this.selectAllMatching = false;

                if (checked) {
                    this.selected = [id];

                    return;
                }

                // Unchecking one row while 'all matching' was active means
                // 'everything except this one' — fetch the full matching id
                // list rather than losing the rest of the selection.
                $wire.call('matchingIds').then(ids => {
                    this.selected = ids.filter(existing => existing !== id);
                });

                return;
            }

            this.selected = checked
                ? [...new Set([...this.selected, id])]
                : this.selected.filter(existing => existing !== id);
        },
        clearSelection() {
            this.selected = [];
            this.selectAllMatching = false;
        },
        confirmDelete() {
            this.showDeleteModal = false;

            if (this.deleteId !== null) {
                this.deletingIds = [this.deleteId];

                $wire.call('destroy', this.deleteId).finally(() => { this.deletingIds = [] });

                return;
            }

            this.deletingIds = this.selectAllMatching ? [...this.pageIds] : [...this.selected];

            const deletion = this.selectAllMatching ? $wire.call('destroyAllMatching') : $wire.call('destroySelected', this.selected);
            deletion.then(() => this.clearSelection()).finally(() => { this.deletingIds = [] });
        },
    }"
>
    <x-ui.livewire-data-table
        :columns="[
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
        ]"
        :items="$teachersLoaded ? $teachers : null"
        :sort="$sort"
        :direction="$direction"
        loadingTarget="search,sortBy,perPage,gotoPage,previousPage,nextPage"
        :selectable="auth()->user()->can('teachers.delete')"
        :showPagination="false"
    >
        @can('teachers.delete')
            <x-slot:selectAll>
                <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll($event.target.checked)" aria-label="Select all teachers on this page">
            </x-slot:selectAll>

            <x-slot:bulkBar>
                <div class="flex items-center justify-between gap-space-md flex-wrap">
                    <div class="flex items-center gap-space-md flex-wrap">
                        <template x-if="!selectAllMatching">
                            <p class="font-label-md text-label-md text-on-surface"><span x-text="selected.length"></span> selected</p>
                        </template>

                        <template x-if="!selectAllMatching && allOnPageSelected && matchingCount > selected.length">
                            <button type="button" @click="selectAllMatching = true" class="font-label-sm text-label-sm text-primary hover:underline">
                                <span x-text="`Select all ${matchingCount} teachers`"></span>
                            </button>
                        </template>

                        <template x-if="selectAllMatching">
                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                <span x-text="`All ${matchingCount} teachers selected.`"></span>
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
            </x-slot:bulkBar>
        @endcan

        @if ($teachersLoaded)
            @forelse ($teachers as $teacher)
                <x-teachers.partials.table-row :teacher="$teacher" />
            @empty
                <tr>
                    <td colspan="4" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                        No teachers found.
                    </td>
                </tr>
            @endforelse
        @endif
    </x-ui.livewire-data-table>

    <!-- Delete Modal -->
    <x-ui.modal show="showDeleteModal" onClose="showDeleteModal = false" maxWidth="max-w-sm">
        <div class="bg-surface border border-outline-variant rounded-lg shadow-lg p-space-lg space-y-space-lg">
            <div class="flex justify-center">
                <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                    <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                </div>
            </div>

            <div class="text-center space-y-space-sm">
                <h3 class="font-headline-sm text-headline-sm text-on-surface" x-text="deleteId === null ? 'Delete Selected Teachers' : 'Delete Teacher'"></h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="deleteId === null ? (selectAllMatching ? `Are you sure you want to delete all ${matchingCount} teachers matching your search? This action cannot be undone.` : `Are you sure you want to delete ${selected.length} selected teacher(s)? This action cannot be undone.`) : 'Are you sure you want to delete this teacher? This action cannot be undone.'"></p>
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
                    @click="confirmDelete()"
                    type="button"
                    class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                >
                    Delete
                </button>
            </div>
        </div>
    </x-ui.modal>
</div>
