@props([
    'auditLogs',
    'perPage' => 15,
    'sort' => 'created_at',
    'direction' => 'desc',
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
    <div wire:loading.block wire:target="dateFrom,dateTo,userId,modelType,event,search,perPage">
        <div class="grid border-b border-outline-variant bg-surface-container" style="grid-template-columns: repeat(6, minmax(0, 1fr));">
            @for ($i = 0; $i < 6; $i++)
                <div class="px-space-lg py-space-md"><div class="h-4 w-24 rounded bg-outline-variant/60 animate-pulse"></div></div>
            @endfor
        </div>
        @for ($row = 0; $row < 5; $row++)
            <div class="grid border-b border-outline-variant last:border-0" style="grid-template-columns: repeat(6, minmax(0, 1fr));">
                @for ($i = 0; $i < 6; $i++)
                    <div class="px-space-lg py-space-md"><div class="h-4 w-full max-w-32 rounded bg-outline-variant/40 animate-pulse"></div></div>
                @endfor
            </div>
        @endfor
    </div>

    <!-- Table (hidden while loading) -->
    <div wire:loading.remove wire:target="dateFrom,dateTo,userId,modelType,event,search,perPage" class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-container border-b border-outline-variant">
                <tr>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('created_at')">
                        <span class="inline-flex items-center gap-space-2xs">
                            Timestamp
                            @if ($sort === 'created_at')
                                <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @else
                                <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                            @endif
                        </span>
                    </th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">User</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Event</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Model</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Description</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse ($auditLogs as $log)
                    <tr wire:key="audit-log-{{ $log->id }}" class="hover:bg-surface-container-lowest transition-colors">
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">{{ $log->created_at_display?->format('M j, Y g:i A') }}</td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $log->user?->email ?? 'system' }}</td>
                        <td class="px-space-lg py-space-md">
                            <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm bg-primary-container text-on-primary-container capitalize">
                                {{ $log->event }}
                            </span>
                        </td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ class_basename($log->auditable_type) }}</td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $log->description }}</td>
                        <td class="px-space-lg py-space-md">
                            <button type="button" wire:click="show('{{ $log->id }}')" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
                                View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                            No audit logs found.
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
        {{ $auditLogs->links() }}
    </div>
</div>
