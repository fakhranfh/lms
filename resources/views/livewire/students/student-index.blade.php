@section('title', 'Students')

<div class="w-full space-y-space-lg" wire:init="loadUsers" x-data="{
        deleteId: null, showDeleteModal: false,
        selected: [],
        get allOnPageSelected() {
            const ids = Array.from(document.querySelectorAll('[data-student-checkbox]')).map(el => el.value);
            return ids.length > 0 && ids.every(id => this.selected.includes(id));
        },
        toggleSelectAll(checked) {
            const ids = Array.from(document.querySelectorAll('[data-student-checkbox]')).map(el => el.value);
            this.selected = checked
                ? [...new Set([...this.selected, ...ids])]
                : this.selected.filter(id => ! ids.includes(id));
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
            @can('students.create')
                <a href="{{ route('students.generate') }}"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                    <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                    Generate Students
                </a>
            @endcan
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

    <!-- Search -->
    <div class="bg-surface rounded-lg p-space-lg border border-outline-variant">
        <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Search</label>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..."
            class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
    </div>

    @can('students.delete')
        <div x-show="selected.length > 0" x-cloak class="flex items-center justify-between px-gutter py-space-md bg-surface-container rounded-lg border border-outline-variant">
            <p class="font-label-md text-label-md text-on-surface"><span x-text="selected.length"></span> selected</p>
            <button type="button" @click="deleteId = null; showDeleteModal = true"
                class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete Selected
            </button>
        </div>
    @endcan

    <!-- Pagination Controls Top -->
    <div class="flex items-center justify-end gap-space-md bg-surface-container rounded-lg p-space-md border border-outline-variant">
        <label class="flex items-center gap-space-sm">
            <span class="font-label-md text-label-md text-on-surface">Per page:</span>
            <select wire:model.live="perPage" class="h-[40px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </label>
    </div>

    <div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
        @unless ($studentsLoaded)
            <div class="animate-pulse p-space-lg space-y-space-md">
                @for ($i = 0; $i < 5; $i++)
                    <div class="h-10 bg-surface-container rounded"></div>
                @endfor
            </div>
        @else
            <div wire:loading.block wire:target="search,sortBy,perPage" class="animate-pulse p-space-lg space-y-space-md">
                @for ($i = 0; $i < 5; $i++)
                    <div class="h-10 bg-surface-container rounded"></div>
                @endfor
            </div>

            <div wire:loading.remove wire:target="search,sortBy,perPage" class="overflow-x-auto">
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
                        @forelse ($students as $student)
                            <tr wire:key="student-{{ $student->id }}" class="hover:bg-surface-container-lowest transition-colors">
                                @can('students.delete')
                                    <td class="px-space-lg py-space-md">
                                        <input type="checkbox" x-model="selected" value="{{ $student->id }}" data-student-checkbox aria-label="Select {{ $student->name }}">
                                    </td>
                                @endcan
                                <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $student->name }}</td>
                                <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $student->email }}</td>
                                <td class="px-space-lg py-space-md text-right whitespace-nowrap space-x-space-md">
                                    @can('students.edit')
                                        <a href="{{ route('students.edit', $student) }}" class="font-label-md text-label-md text-primary hover:underline">Edit</a>
                                    @endcan
                                    @can('students.delete')
                                        <button type="button" @click="deleteId = '{{ $student->id }}'; showDeleteModal = true" class="font-label-md text-label-md text-error hover:underline">Delete</button>
                                    @endcan
                                </td>
                            </tr>
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

    <div class="flex items-center justify-between gap-space-md bg-surface-container rounded-lg p-space-md border border-outline-variant">
        <div></div>
        <div>
            @if ($studentsLoaded)
                {{ $students->links() }}
            @endif
        </div>
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
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="deleteId === null ? `Are you sure you want to delete ${selected.length} selected student(s)? This action cannot be undone.` : 'Are you sure you want to delete this student? This action cannot be undone.'"></p>
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
                            @click="showDeleteModal = false; if (deleteId === null) { $wire.call('destroySelected', selected); selected = [] } else { $wire.call('destroy', deleteId) }"
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
