@section('title', 'Users')

<div class="space-y-space-lg" wire:init="loadUsers" x-data="{
        deleteId: null, showDeleteModal: false,
        selected: [],
        get allOnPageSelected() {
            const ids = Array.from(document.querySelectorAll('[data-user-checkbox]')).map(el => el.value);
            return ids.length > 0 && ids.every(id => this.selected.includes(id));
        },
        toggleSelectAll(checked) {
            const ids = Array.from(document.querySelectorAll('[data-user-checkbox]')).map(el => el.value);
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
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Users</h1>
        <div class="flex items-center gap-space-md">
            @can('users.import')
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                        class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        Import Users
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </button>
                    <div x-show="open" x-cloak x-transition
                        class="absolute right-0 mt-space-xs w-56 bg-surface border border-outline-variant rounded-lg shadow-lg z-10 overflow-hidden">
                        <a href="{{ route('admin.users.import', ['role' => 'teacher']) }}"
                            class="flex items-center gap-space-sm px-space-lg py-space-sm font-body-md text-body-md text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">school</span>
                            Import Teachers
                        </a>
                        <a href="{{ route('admin.users.import', ['role' => 'student']) }}"
                            class="flex items-center gap-space-sm px-space-lg py-space-sm font-body-md text-body-md text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">groups</span>
                            Import Students
                        </a>
                    </div>
                </div>
            @endcan
            @can('users.edit')
                <a href="{{ route('admin.users.photos') }}"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                    <span class="material-symbols-outlined text-[18px]">add_a_photo</span>
                    Bulk Upload Photos
                </a>
            @endcan
            @can('users.create')
                <a href="{{ route('admin.users.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">Create User</a>
            @endcan
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-surface rounded-lg p-space-lg border border-outline-variant">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..."
                    class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
            </div>
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Filter by Role</label>
                <select wire:model.live="filterRole" class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                    <option value="">All Roles</option>
                    @foreach ($this->availableRoles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @can('users.delete')
        <div x-show="selected.length > 0" x-cloak class="flex items-center justify-between px-gutter py-space-md bg-surface-container rounded-lg border border-outline-variant">
            <p class="font-label-md text-label-md text-on-surface"><span x-text="selected.length"></span> selected</p>
            <button type="button" @click="deleteId = null; showDeleteModal = true"
                class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete Selected
            </button>
        </div>
    @endcan

    <x-users.table :users="$users" :usersLoaded="$usersLoaded" :sort="$sort" :direction="$direction" :perPage="$perPage" />

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
        <!-- Overlay -->
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

        <!-- Modal -->
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
                        <h3 class="font-headline-sm text-headline-sm text-on-surface" x-text="deleteId === null ? 'Delete Selected Users' : 'Delete User'"></h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="deleteId === null ? `Are you sure you want to delete ${selected.length} selected user(s)? This action cannot be undone.` : 'Are you sure you want to delete this user? This action cannot be undone.'"></p>
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
