@props([
    'users',
    'usersLoaded' => true,
    'sort' => 'name',
    'direction' => 'asc',
    'perPage' => 15,
])

<x-ui.livewire-data-table
    :columns="[
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'roles', 'label' => 'Roles', 'sortable' => false],
    ]"
    :items="$usersLoaded ? $users : null"
    :sort="$sort"
    :direction="$direction"
    :perPage="$perPage"
    loadingTarget="search,filterRole,sortBy,perPage"
    :selectable="auth()->user()->can('users.delete')"
>
    @can('users.delete')
        <x-slot:selectAll>
            <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll($event.target.checked)" aria-label="Select all users on this page">
        </x-slot:selectAll>
    @endcan

    @if ($usersLoaded)
        @forelse ($users as $user)
            <tr wire:key="user-{{ $user->id }}" class="hover:bg-surface-container-lowest transition-colors">
                @can('users.delete')
                    <td class="px-space-lg py-space-md">
                        @unless ($user->id === auth()->id())
                            <input type="checkbox" x-model="selected" value="{{ $user->id }}" data-user-checkbox aria-label="Select {{ $user->name }}">
                        @endunless
                    </td>
                @endcan
                <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $user->name }}</td>
                <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $user->email }}</td>
                <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">
                    {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                </td>
                <td class="px-space-lg py-space-md text-right whitespace-nowrap space-x-space-md">
                    @can('users.edit')
                        <a href="{{ route('users.edit', $user) }}" class="font-label-md text-label-md text-primary hover:underline">Edit</a>
                    @endcan
                    <a href="{{ route('users.roles.edit', $user) }}" class="font-label-md text-label-md text-primary hover:underline">Assign Roles</a>
                    @can('users.delete')
                        @unless ($user->id === auth()->id())
                            <button type="button" @click="deleteId = '{{ $user->id }}'; showDeleteModal = true" class="font-label-md text-label-md text-error hover:underline">Delete</button>
                        @endunless
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ auth()->user()->can('users.delete') ? 5 : 4 }}" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                    No users found.
                </td>
            </tr>
        @endforelse
    @endif
</x-ui.livewire-data-table>
