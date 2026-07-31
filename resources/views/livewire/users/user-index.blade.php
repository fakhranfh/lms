@section('title', 'Users')

<div class="space-y-space-lg">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
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
                        <a href="{{ route('users.import', ['role' => 'teacher']) }}"
                            class="flex items-center gap-space-sm px-space-lg py-space-sm font-body-md text-body-md text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">school</span>
                            Import Teachers
                        </a>
                        <a href="{{ route('users.import', ['role' => 'student']) }}"
                            class="flex items-center gap-space-sm px-space-lg py-space-sm font-body-md text-body-md text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">groups</span>
                            Import Students
                        </a>
                    </div>
                </div>
            @endcan
            @can('users.create')
                <a href="{{ route('users.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">Create User</a>
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

    <x-users.table :users="$users" :sort="$sort" :direction="$direction" :perPage="$perPage" />
</div>
