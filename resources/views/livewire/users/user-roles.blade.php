@section('title', 'Assign Roles')

<div class="max-w-2xl">
    @error('roles')
        <div class="mb-space-lg px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $message }}</p>
        </div>
    @enderror

    <form wire:submit="updateRoles" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div>
            <p class="font-label-md text-label-md text-secondary uppercase mb-space-xs">User</p>
            <p class="font-body-md text-body-md text-on-surface">{{ $user->name }} ({{ $user->email }})</p>
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
        </div>

        <div class="flex items-center gap-space-md">
            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Save
            </button>
            <a href="{{ route('roles.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
        </div>
    </form>
</div>
