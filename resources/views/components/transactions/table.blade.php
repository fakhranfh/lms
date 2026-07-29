@props([
    'transactions',
    'perPage' => 15,
    'sort' => 'created_at',
    'direction' => 'desc',
    'filterTargets' => 'dateFrom,dateTo,status,transactionType,gatewayId,search,perPage',
    'actionsView' => 'components.transactions.admin-actions',
])

@php
    $statusClasses = [
        'pending' => 'bg-tertiary-container text-on-tertiary-container',
        'completed' => 'bg-primary-container text-on-primary-container',
        'failed' => 'bg-error-container text-on-error-container',
        'refunded' => 'bg-outline-variant text-on-surface',
    ];
@endphp

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
    <div wire:loading.block wire:target="{{ $filterTargets }}">
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
    <div wire:loading.remove wire:target="{{ $filterTargets }}" class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-container border-b border-outline-variant">
                <tr>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('created_at')">
                        <span class="inline-flex items-center gap-space-2xs">
                            Date
                            @if ($sort === 'created_at')
                                <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @else
                                <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                            @endif
                        </span>
                    </th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Transaction ID</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Gateway</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('amount')">
                        <span class="inline-flex items-center gap-space-2xs">
                            Amount
                            @if ($sort === 'amount')
                                <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @else
                                <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                            @endif
                        </span>
                    </th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Status</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse ($transactions as $transaction)
                    <tr wire:key="transaction-{{ $transaction->id }}" class="hover:bg-surface-container-lowest transition-colors">
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">{{ $transaction->created_at_display?->format('M j, Y g:i A') }}</td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $transaction->transaction_id }}</td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $transaction->paymentGateway?->paymentGatewayType?->label ?? '—' }}</td>
                        <td class="px-space-lg py-space-md text-body-md text-on-surface">Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                        <td class="px-space-lg py-space-md">
                            <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm capitalize {{ $statusClasses[$transaction->status->value] ?? 'bg-outline-variant text-on-surface' }}">
                                {{ $transaction->status->label() }}
                            </span>
                        </td>
                        <td class="px-space-lg py-space-md space-x-space-sm whitespace-nowrap">
                            @include($actionsView, ['transaction' => $transaction])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                            No transactions found.
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
        {{ $transactions->links() }}
    </div>
</div>
