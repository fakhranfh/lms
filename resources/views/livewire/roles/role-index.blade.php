@section('title', 'Roles')

<div class="space-y-space-lg" x-data="{ deleteId: null, showModal: false }">
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
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Roles</h1>
        <a href="{{ route('roles.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            New Role
        </a>
    </div>

    <x-ui.livewire-data-table
        :columns="[
            ['key' => 'name', 'label' => 'Name', 'sortable' => false],
            ['key' => 'permissions', 'label' => 'Permissions', 'sortable' => false],
        ]"
        :items="$roles"
        loadingTarget="destroy"
    >
        @forelse ($roles as $role)
            <tr class="border-b border-outline-variant last:border-0">
                <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $role->name }}</td>
                <td class="px-space-lg py-space-md font-body-sm text-body-sm text-secondary">
                    {{ $role->permissions->map(fn ($permission) => $permission->label ?? $permission->name)->join(', ') ?: '—' }}
                </td>
                <td class="px-space-lg py-space-md text-right space-x-space-sm whitespace-nowrap">
                    <a href="{{ route('roles.edit', $role) }}" class="font-label-md text-label-md text-primary hover:underline">Edit</a>
                    @unless ($role->name === 'admin')
                        <button type="button" @click="deleteId = {{ $role->id }}; showModal = true" class="font-label-md text-label-md text-error hover:underline">Delete</button>
                    @endunless
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-space-lg py-space-lg text-center font-body-md text-body-md text-secondary">No roles yet.</td>
            </tr>
        @endforelse
    </x-ui.livewire-data-table>

    <!-- Delete Modal -->
    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 z-50"
    >
        <!-- Overlay -->
        <div
            @click="showModal = false"
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
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Role</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Are you sure you want to delete this role? This action cannot be undone.</p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            @click="showModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="showModal = false; $wire.call('destroy', deleteId)"
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
