@props([
    'users',
    'usersLoaded' => true,
    'sort' => 'name',
    'direction' => 'asc',
    'perPage' => 15,
])

<!-- Pagination Controls Top -->
<div class="flex items-center justify-end gap-space-md bg-surface-container rounded-lg p-space-md border border-outline-variant">
    <label class="flex items-center gap-space-sm">
        <span class="font-label-md text-label-md text-on-surface">Per page:</span>
        <select wire:model.live="perPage" class="h-[40px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </label>
</div>

<!-- Table -->
<div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
    @unless ($usersLoaded)
        @include('components.users.partials.table-skeleton')
    @else
        <!-- Skeleton (shown while a search/filter/sort/perPage update is in flight) -->
        <div wire:loading.block wire:target="search,filterRole,sortBy,perPage">
            @include('components.users.partials.table-skeleton')
        </div>

        <!-- Table (hidden while loading) -->
        <div wire:loading.remove wire:target="search,filterRole,sortBy,perPage" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-container border-b border-outline-variant">
                    <tr>
                        @can('users.delete')
                            <th class="px-space-lg py-space-md w-[1%]">
                                <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll($event.target.checked)" aria-label="Select all users on this page">
                            </th>
                        @endcan
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('name')">
                            <span class="inline-flex items-center gap-space-2xs">
                                Name
                                @if ($sort === 'name')
                                    <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                                @endif
                            </span>
                        </th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('email')">
                            <span class="inline-flex items-center gap-space-2xs">
                                Email
                                @if ($sort === 'email')
                                    <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                                @endif
                            </span>
                        </th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Roles</th>
                        <th class="px-space-lg py-space-md text-right font-label-md text-label-md text-on-surface">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
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
                </tbody>
            </table>
        </div>
    @endunless
</div>

<!-- Pagination Controls Bottom -->
<div class="flex items-center justify-between gap-space-md bg-surface-container rounded-lg p-space-md border border-outline-variant">
    <label class="flex items-center gap-space-sm">
        <span class="font-label-md text-label-md text-on-surface">Per page:</span>
        <select wire:model.live="perPage" class="h-[40px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </label>
    <div>
        @if ($usersLoaded)
            {{ $users->links() }}
        @endif
    </div>
</div>
