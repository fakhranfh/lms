@section('title', 'Bulk Upload Photos')

<div class="space-y-space-lg">
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
        <a href="{{ route('users.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Back to Users</a>
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

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-space-md">
        @forelse ($users as $user)
            @php
                $initial = strtoupper(substr($user->name ?: 'U', 0, 1));
                $defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%231E3A8A%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22 font-size=%2250%22 font-weight=%22bold%22%3E'.$initial.'%3C/text%3E%3C/svg%3E';
                $currentSrc = $stagedPhotoUrls[$user->id] ?? $user->profile_photo_path ?? $defaultAvatar;
            @endphp
            <div wire:key="photo-{{ $user->id }}" x-data="{ preview: null }"
                class="flex flex-col items-center gap-space-xs p-space-md bg-surface border border-outline-variant rounded-lg">
                <label class="relative group/avatar cursor-pointer w-20 h-20 rounded-full overflow-hidden border-2 border-surface-container-low shadow-sm"
                    wire:loading.class="opacity-50" wire:target="uploads.{{ $user->id }}">
                    <img :src="preview || @js($currentSrc)" alt="{{ $user->name }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-on-surface/40 flex items-center justify-center opacity-0 group-hover/avatar:opacity-100 transition-opacity duration-200">
                        <span class="material-symbols-outlined text-surface text-[20px]">photo_camera</span>
                    </div>
                    @if (isset($stagedPhotoUrls[$user->id]))
                        <span class="absolute bottom-0 right-0 w-5 h-5 bg-success rounded-full flex items-center justify-center border-2 border-surface">
                            <span class="material-symbols-outlined text-white text-[12px]" data-weight="fill">check</span>
                        </span>
                    @endif
                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/gif"
                        wire:model="uploads.{{ $user->id }}"
                        @change="
                            if ($event.target.files[0]) {
                                const reader = new FileReader();
                                reader.onload = e => preview = e.target.result;
                                reader.readAsDataURL($event.target.files[0]);
                            }
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

    <div>
        {{ $users->links() }}
    </div>
</div>
