@section('title', 'Edit Role')

<div class="max-w-2xl" x-data='{
    selectedPermissions: @json($permissions),
    allPermissionIds: @json($groupedPermissions->flatten()->pluck("id")->all()),
    permissionsByGroup: @json($groupedPermissions->mapWithKeys(fn($perms, $group) => [$group => $perms->pluck("id")->all()])->all()),

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
}'>
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
            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Save
            </button>
            <a href="{{ route('roles.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
        </div>
    </form>
</div>
