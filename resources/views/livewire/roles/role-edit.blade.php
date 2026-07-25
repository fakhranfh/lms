@section('title', 'Edit Role')

<div class="max-w-2xl" x-data='{
    selectedPermissions: @json($permissions),
    allPermissionIds: @json($groupedPermissions->flatten()->pluck("id")->all()),
    permissionsByGroup: @json($groupedPermissions->mapWithKeys(fn($perms, $group) => [$group => $perms->pluck("id")->all()])->all()),
    showErrorModal: false,
    errorMessage: "",

    isAllSelected() {
        return this.allPermissionIds.length > 0 && this.allPermissionIds.every(id => this.selectedPermissions.includes(id));
    },

    selectAll() {
        this.selectedPermissions = [...this.allPermissionIds];
    },

    deselectAll() {
        this.selectedPermissions = [];
    },

    isGroupSelected(group) {
        const groupIds = this.permissionsByGroup[group] || [];
        return groupIds.length > 0 && groupIds.every(id => this.selectedPermissions.includes(id));
    },

    toggleGroup(group) {
        const groupIds = this.permissionsByGroup[group] || [];
        if (this.isGroupSelected(group)) {
            this.selectedPermissions = this.selectedPermissions.filter(id => !groupIds.includes(id));
        } else {
            this.selectedPermissions = [...new Set([...this.selectedPermissions, ...groupIds])];
        }
    }
}' x-init="$wire.$on('show-error-modal', ({ message }) => { errorMessage = message; showErrorModal = true })">
    <form wire:submit="update" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg"
        @submit="$el.querySelector('[name=permissions_json]').value = JSON.stringify(selectedPermissions)">
        <div>
            <label for="name" class="block font-label-md text-label-md text-on-surface mb-space-xs">Name</label>
            <input type="text" wire:model="name" id="name"
                {{ $role->name === 'admin' ? 'readonly' : '' }}
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
            @error('name')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Hidden input to sync selected permissions to Livewire -->
        <input type="hidden" name="permissions_json" wire:model.live="permissionsJson" value="[]">

        <div>
            <div class="flex items-center justify-between mb-space-sm">
                <label class="font-label-md text-label-md text-on-surface">Permissions</label>
                <label class="flex items-center gap-space-sm font-label-sm text-label-sm text-on-surface cursor-pointer">
                    <input type="checkbox" @change="$event.target.checked ? selectAll() : deselectAll()" :checked="isAllSelected()">
                    Select All
                </label>
            </div>
            <div class="space-y-space-lg divide-y divide-outline-variant">
                @foreach ($groupedPermissions as $group => $groupPermissions)
                    <div class="{{ $loop->first ? '' : 'pt-space-lg' }}">
                        <label class="flex items-center gap-space-sm font-label-sm text-label-sm text-secondary uppercase mb-space-xs cursor-pointer">
                            <input type="checkbox" @change="toggleGroup('{{ $group }}')" :checked="isGroupSelected('{{ $group }}')">
                            {{ $group }}
                        </label>
                        <div class="grid grid-cols-2 gap-space-sm">
                            @foreach ($groupPermissions as $permission)
                                <label class="flex items-center gap-space-sm font-body-md text-body-md text-on-surface cursor-pointer">
                                    <input type="checkbox" x-model.number="selectedPermissions" value="{{ $permission->id }}">
                                    {{ $permission->label ?? $permission->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('permissions')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-space-md">
            <button type="submit" wire:loading.attr="disabled" wire:target="update" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                <svg wire:loading wire:target="update" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="update">Save</span>
                <span wire:loading wire:target="update">Saving...</span>
            </button>
            <a href="{{ route('roles.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
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
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Unable to Save Role</h3>
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
