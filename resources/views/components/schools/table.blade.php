@props([
    'schools',
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
    <div wire:loading.block wire:target="search,filterTier,sortBy,perPage">
        <div class="grid border-b border-outline-variant bg-surface-container" style="grid-template-columns: repeat(5, minmax(0, 1fr));">
            @for ($i = 0; $i < 5; $i++)
                <div class="px-space-lg py-space-md"><div class="h-4 w-24 rounded bg-outline-variant/60 animate-pulse"></div></div>
            @endfor
        </div>
        @for ($row = 0; $row < 5; $row++)
            <div class="grid border-b border-outline-variant last:border-0" style="grid-template-columns: repeat(5, minmax(0, 1fr));">
                @for ($i = 0; $i < 5; $i++)
                    <div class="px-space-lg py-space-md"><div class="h-4 w-full max-w-32 rounded bg-outline-variant/40 animate-pulse"></div></div>
                @endfor
            </div>
        @endfor
    </div>

    <!-- Table (hidden while loading) -->
    <div wire:loading.remove wire:target="search,filterTier,sortBy,perPage" class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-container border-b border-outline-variant">
                <tr>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('name')">
                        <span class="inline-flex items-center gap-space-2xs">
                            School Name
                            @if ($sort === 'name')
                                <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @else
                                <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                            @endif
                        </span>
                    </th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('domain')">
                        <span class="inline-flex items-center gap-space-2xs">
                            Domain
                            @if ($sort === 'domain')
                                <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @else
                                <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                            @endif
                        </span>
                    </th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Tier</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('created_at')">
                        <span class="inline-flex items-center gap-space-2xs">
                            Created
                            @if ($sort === 'created_at')
                                <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @else
                                <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                            @endif
                        </span>
                    </th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($schools as $school)
                    <tr wire:key="school-{{ $school->id }}" class="hover:bg-surface-container-lowest transition-colors">
                        <td class="px-space-lg py-space-md text-body-md text-on-surface">
                            <a href="{{ route('admin.schools.edit', $school) }}" class="text-primary hover:underline">
                                {{ $school->name }}
                            </a>
                        </td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $school->domain }}</td>
                        <td class="px-space-lg py-space-md">
                            <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm bg-primary-container text-on-primary-container">
                                {{ $school->tier->name }}
                            </span>
                        </td>
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">
                            {{ $school->created_at_display->format('M d, Y') }}
                        </td>
                        <td class="px-space-lg py-space-md">
                            <div class="flex gap-space-sm">
                                <a href="{{ route('admin.schools.edit', $school) }}" class="px-space-md py-space-xs rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-on-primary-fixed-variant transition-colors">
                                    Edit
                                </a>
                                <a href="{{ route('admin.schools.tier-history', $school) }}" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
                                    History
                                </a>
                                @unless ($school->domain === config('app.domain'))
                                    <button type="button" @click="deleteId = '{{ $school->id }}'; showModal = true" class="px-space-md py-space-xs rounded-lg bg-error text-on-error font-label-sm text-label-sm hover:opacity-90 transition-opacity">
                                        Delete
                                    </button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                            No schools found.
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
        {{ $schools->links() }}
    </div>
</div>
