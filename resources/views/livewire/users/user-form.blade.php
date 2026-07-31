@section('title', $this->isEditing() ? 'Edit User' : 'New User')

<div class="max-w-2xl" x-data="{ showErrorModal: false, errorMessage: '' }"
    x-init="$wire.$on('show-error-modal', ({ message }) => { errorMessage = message; showErrorModal = true })">
    <form wire:submit="save" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <!-- Profile Photo -->
        <div class="flex flex-col md:flex-row items-center md:items-start gap-space-lg pb-space-lg border-b border-outline-variant">
            <label for="photo" class="relative group/avatar cursor-pointer flex-shrink-0">
                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-surface-container-low shadow-sm relative hover:shadow-lg transition-shadow">
                    @php
                        $userInitial = strtoupper(substr($name ?: 'U', 0, 1));
                        $defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%231E3A8A%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22 font-size=%2250%22 font-weight=%22bold%22%3E' . $userInitial . '%3C/text%3E%3C/svg%3E';
                        $previewSrc = ($photo && $photo->isPreviewable()) ? $photo->temporaryUrl() : ($photoPath ?: $defaultAvatar);
                    @endphp
                    <img src="{{ $previewSrc }}" alt="Photo" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-on-surface/40 flex items-center justify-center opacity-0 group-hover/avatar:opacity-100 transition-opacity duration-200">
                        <span class="material-symbols-outlined text-surface text-[28px]">photo_camera</span>
                    </div>
                </div>
            </label>
            <input type="file" id="photo" wire:model="photo" accept="image/jpeg,image/png,image/gif" class="hidden" />
            <div class="text-center md:text-left">
                <h3 class="font-label-md text-label-md text-on-surface">Photo</h3>
                <p class="font-body-sm text-body-sm text-secondary mt-space-xs">JPG, GIF or PNG. Max size of 5MB.</p>
                <label for="photo" class="mt-space-sm inline-block font-label-md text-label-md text-primary hover:underline cursor-pointer">Change Photo</label>
                @error('photo')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="name" class="block font-label-md text-label-md text-on-surface mb-space-xs">Name</label>
            <input type="text" wire:model="name" id="name"
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
            @error('name')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block font-label-md text-label-md text-on-surface mb-space-xs">Email</label>
            <input type="email" wire:model="email" id="email"
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
            @error('email')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block font-label-md text-label-md text-on-surface mb-space-xs">
                Password {{ $this->isEditing() ? '(leave blank to keep current)' : '' }}
            </label>
            <input type="password" wire:model="password" id="password" autocomplete="new-password"
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
            @error('password')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Roles</label>
            <div class="space-y-space-sm">
                @foreach ($allRoles as $role)
                    <label class="flex items-center gap-space-sm font-body-md text-body-md text-on-surface">
                        <input type="checkbox" wire:model="roles" value="{{ $role->id }}">
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>
            @error('roles')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-space-md">
            <button type="submit" wire:loading.attr="disabled" wire:target="save,photo" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
            <a href="{{ route('users.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
        </div>
    </form>

    <!-- Error Modal -->
    <div x-show="showErrorModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showErrorModal = false"
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
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">error</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Unable to Save User</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="errorMessage"></p>
                    </div>

                    <div class="flex pt-space-md">
                        <button
                            @click="showErrorModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
