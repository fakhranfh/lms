@section('title', 'Bulk Upload Photos')

<div class="space-y-space-lg" wire:init="loadUsers" x-data="{
        get hasUnsavedPhotos() { return Object.keys($wire.stagedPhotoUrls).length > 0 },
    }"
    x-init="
        window.addEventListener('beforeunload', (event) => {
            if (! hasUnsavedPhotos) { return; }

            event.preventDefault();
            event.returnValue = '';
        });
    ">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Bulk Upload Photos</h1>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">Click a user's photo to choose a replacement. Nothing is final until you press Save.</p>
        </div>
        <button type="button" onclick="window.location.href='{{ route('users.index') }}'"
            class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors">
            Back to Users
        </button>
    </div>

    <!-- Search and Filter -->
    <div class="bg-surface rounded-lg p-space-lg border border-outline-variant">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name..."
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

    @if (! empty($stagedPhotoUrls))
        <div class="sticky top-0 z-10 flex items-center justify-between px-gutter py-space-md bg-surface-container rounded-lg border border-outline-variant shadow-sm">
            <p class="font-label-md text-label-md text-on-surface">{{ count($stagedPhotoUrls) }} photo(s) ready to save</p>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    @endif

    <!-- Pagination Controls Top -->
    <div class="flex items-center justify-end gap-space-md bg-surface-container rounded-lg p-space-md border border-outline-variant">
        <label class="flex items-center gap-space-sm">
            <span class="font-label-md text-label-md text-on-surface">Per page:</span>
            <select wire:model.live="perPage" class="h-[40px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                <option value="20">20</option>
                <option value="40">40</option>
                <option value="60">60</option>
                <option value="100">100</option>
            </select>
        </label>
    </div>

    @if (! $usersLoaded)
        @include('livewire.users.partials.photo-grid-skeleton')
    @else
    <!-- Skeleton (shown while loading) -->
    <div wire:loading.block wire:target="search,filterRole,perPage">
        @include('livewire.users.partials.photo-grid-skeleton')
    </div>

    <!-- Photo grid (hidden while loading) -->
    <div wire:loading.remove wire:target="search,filterRole,perPage" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-space-md">
        @forelse ($users as $user)
            @php
                $initial = strtoupper(substr($user->name ?: 'U', 0, 1));
                $defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%231E3A8A%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22 font-size=%2250%22 font-weight=%22bold%22%3E'.$initial.'%3C/text%3E%3C/svg%3E';
                $currentSrc = $stagedPhotoUrls[$user->id] ?? $user->profile_photo_path ?? $defaultAvatar;
            @endphp
            <div wire:key="photo-{{ $user->id }}" x-data="{ preview: null, uploading: false, progress: 0 }"
                class="flex flex-col items-center gap-space-xs p-space-md bg-surface border border-outline-variant rounded-lg">
                <label class="relative group/avatar cursor-pointer w-20 h-20 rounded-full overflow-hidden border-2 border-surface-container-low shadow-sm">
                    <img :src="preview || @js($currentSrc)" alt="{{ $user->name }}" class="w-full h-full object-cover" :class="{ 'opacity-50': uploading }">
                    <div class="absolute inset-0 bg-on-surface/40 flex items-center justify-center transition-opacity duration-200"
                        :class="uploading ? 'opacity-100' : 'opacity-0 group-hover/avatar:opacity-100'">
                        <template x-if="!uploading">
                            <span class="material-symbols-outlined text-surface text-[20px]">photo_camera</span>
                        </template>
                        <template x-if="uploading">
                            <span class="font-label-sm text-label-sm text-surface" x-text="progress + '%'"></span>
                        </template>
                    </div>
                    <div x-show="uploading" x-cloak class="absolute bottom-0 left-0 right-0 h-1 bg-black/30">
                        <div class="h-full bg-primary transition-all" :style="`width: ${progress}%`"></div>
                    </div>
                    @if (isset($stagedPhotoUrls[$user->id]))
                        <span class="absolute top-0 right-0 w-5 h-5 bg-success rounded-full flex items-center justify-center border-2 border-surface">
                            <span class="material-symbols-outlined text-white text-[12px]" data-weight="fill">check</span>
                        </span>
                    @endif
                    <!-- Plain (non-wire:model) file input, uploaded manually via $wire.upload
                         so the progress event can drive the overlay above. -->
                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/gif"
                        @change="
                            const file = $event.target.files[0];
                            if (! file) { return; }

                            const reader = new FileReader();
                            reader.onload = e => preview = e.target.result;
                            reader.readAsDataURL(file);

                            uploading = true;
                            progress = 0;

                            $wire.upload('uploads.{{ $user->id }}', file,
                                () => { uploading = false; },
                                () => { uploading = false; },
                                (event) => { progress = event.detail.progress; },
                            );
                        ">
                </label>
                <div class="text-center">
                    <p class="font-label-sm text-label-sm text-on-surface truncate max-w-[7rem]">{{ $user->name }}</p>
                    <p class="font-body-sm text-body-sm text-secondary truncate max-w-[7rem]">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</p>
                </div>
            </div>
        @empty
            <p class="col-span-full text-center font-body-md text-body-md text-secondary py-space-lg">No users found.</p>
        @endforelse
    </div>
    @endif

    <!-- Pagination Controls Bottom -->
    <div class="flex items-center justify-between gap-space-md bg-surface-container rounded-lg p-space-md border border-outline-variant">
        <label class="flex items-center gap-space-sm">
            <span class="font-label-md text-label-md text-on-surface">Per page:</span>
            <select wire:model.live="perPage" class="h-[40px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                <option value="20">20</option>
                <option value="40">40</option>
                <option value="60">60</option>
                <option value="100">100</option>
            </select>
        </label>
        <div>
            @if ($usersLoaded)
                {{ $users->links() }}
            @endif
        </div>
    </div>
</div>
