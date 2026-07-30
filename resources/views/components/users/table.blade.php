@props([
    'users',
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
    <!-- Skeleton (shown while loading) -->
    <div wire:loading.block wire:target="search,filterRole,sortBy,perPage">
        <div class="grid border-b border-outline-variant bg-surface-container" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
            @for ($i = 0; $i < 4; $i++)
                <div class="px-space-lg py-space-md"><div class="h-4 w-24 rounded bg-outline-variant/60 animate-pulse"></div></div>
            @endfor
        </div>
        @for ($row = 0; $row < 5; $row++)
            <div class="grid border-b border-outline-variant last:border-0" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                @for ($i = 0; $i < 4; $i++)
                    <div class="px-space-lg py-space-md"><div class="h-4 w-full max-w-32 rounded bg-outline-variant/40 animate-pulse"></div></div>
                @endfor
            </div>
        @endfor
    </div>

    <!-- Table (hidden while loading) -->
    <div wire:loading.remove wire:target="search,filterRole,sortBy,perPage" class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-container border-b border-outline-variant">
                <tr>
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
                        <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $user->name }}</td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $user->email }}</td>
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">
                            {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="px-space-lg py-space-md text-right whitespace-nowrap">
                            <a href="{{ route('users.roles.edit', $user) }}" class="font-label-md text-label-md text-primary hover:underline">Assign Roles</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
        {{ $users->links() }}
    </div>
</div>
