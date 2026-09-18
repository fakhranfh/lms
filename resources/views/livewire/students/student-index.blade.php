@section('title', 'Students')

<div class="w-full space-y-space-lg" wire:init="loadUsers" x-data="{
        deleteId: null, showDeleteModal: false,
        showGenerateModal: false,
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
                this.selected = checked ? [id] : [];
            } else {
                const previous = this.selected;
                this.selected = checked
                    ? [...new Set([...this.selected, id])]
                    : this.selected.filter(existing => existing !== id);

                fetch('{{ route('students.selection.update') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ id, checked }),
                }).catch(() => { this.selected = previous; });
            }
        },
        clearSelection() {
            this.selected = [];
            this.selectAllMatching = false;

            fetch('{{ route('students.selection.clear') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
            }).catch(() => {});
        },
    }">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Students</h1>
        <div class="flex items-center gap-space-md">
            @if (app()->environment(['local', 'testing']))
                @can('students.create')
                    <button type="button" @click="showGenerateModal = true"
                        class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                        <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                        Generate Students
                    </button>
                @endcan
            @endif
            @can('students.import')
                <a href="{{ route('students.import') }}"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    Import Students
                </a>
            @endcan
            @can('students.create')
                <a href="{{ route('students.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">Create Student</a>
            @endcan
        </div>
    </div>

    @if ($studentsLoaded)
        <x-ui.pagination-links
            :paginator="$students"
            perPageModel="perPage"
            :perPageOptions="[10, 15, 25, 50]"
            searchModel="search"
            searchPlaceholder="Search by name or email..."
            :search="$search"
        />
    @endif

    <x-students.table
        :students="$students"
        :students-loaded="$studentsLoaded"
        :sort="$sort"
        :direction="$direction"
    />

    @if ($studentsLoaded)
        <x-ui.pagination-links
            :paginator="$students"
            perPageModel="perPage"
            :perPageOptions="[10, 15, 25, 50]"
        />
    @endif

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

    <!-- Generate Students Modal -->
    @if (app()->environment(['local', 'testing']))
        @can('students.create')
            <div x-show="showGenerateModal" x-cloak class="fixed inset-0 z-50">
                <div
                    @click="showGenerateModal = false"
                    class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                ></div>

                <div
                    class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-2xl w-full my-space-lg" @click.stop>
                        <div class="p-space-lg">
                            <livewire:students.student-generate :embedded="true" wire:key="student-generate-modal" />
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    @endif
</div>
