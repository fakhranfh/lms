@section('title', 'Transaction Summary')

@php
    $statusClasses = [
        'pending' => 'bg-tertiary-container text-on-tertiary-container',
        'completed' => 'bg-primary-container text-on-primary-container',
        'failed' => 'bg-error-container text-on-error-container',
        'refunded' => 'bg-outline-variant text-on-surface',
    ];
@endphp

<div class="space-y-space-lg">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Transaction Summary</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Revenue overview across all schools</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
        <input type="date" wire:model.live="dateFrom" placeholder="From" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <input type="date" wire:model.live="dateTo" placeholder="To" class="px-space-md py-space-sm border border-outline rounded-lg" />
    </div>

    <!-- Skeleton (shown while loading) -->
    <div wire:loading.block wire:target="dateFrom,dateTo" class="space-y-space-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            @for ($i = 0; $i < 2; $i++)
                <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
                    <div class="h-4 w-40 rounded bg-outline-variant/60 animate-pulse"></div>
                    <div class="h-7 w-32 rounded bg-outline-variant/40 animate-pulse mt-space-sm"></div>
                </div>
            @endfor
        </div>

        @for ($table = 0; $table < 2; $table++)
            <div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
                <div class="grid grid-cols-3 border-b border-outline-variant bg-surface-container">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="px-space-lg py-space-md"><div class="h-4 w-24 rounded bg-outline-variant/60 animate-pulse"></div></div>
                    @endfor
                </div>
                @for ($row = 0; $row < 4; $row++)
                    <div class="grid grid-cols-3 border-b border-outline-variant last:border-0">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="px-space-lg py-space-md"><div class="h-4 w-full max-w-32 rounded bg-outline-variant/40 animate-pulse"></div></div>
                        @endfor
                    </div>
                @endfor
            </div>
        @endfor
    </div>

    <!-- Content (hidden while loading) -->
    <div wire:loading.remove wire:target="dateFrom,dateTo" class="space-y-space-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Total Revenue (Completed)</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">Rp {{ number_format((float) $totalRevenue, 0, ',', '.') }}</p>
            </div>
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Total Transactions</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ number_format($totalTransactions) }}</p>
            </div>
        </div>

        <div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
            <div class="px-space-lg py-space-md border-b border-outline-variant">
                <h2 class="font-title-md text-title-md text-on-surface">Breakdown by Status</h2>
            </div>
            <table class="w-full">
                <thead class="bg-surface-container border-b border-outline-variant">
                    <tr>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Status</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Transactions</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @foreach ($statuses as $statusOption)
                        @php $row = $byStatus->get($statusOption->value); @endphp
                        <tr>
                            <td class="px-space-lg py-space-md">
                                <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm capitalize {{ $statusClasses[$statusOption->value] ?? 'bg-outline-variant text-on-surface' }}">
                                    {{ $statusOption->label() }}
                                </span>
                            </td>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ number_format($row->total_count ?? 0) }}</td>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface">Rp {{ number_format((float) ($row->total_amount ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
            <div class="px-space-lg py-space-md border-b border-outline-variant">
                <h2 class="font-title-md text-title-md text-on-surface">Revenue by Gateway (Completed)</h2>
            </div>
            <table class="w-full">
                <thead class="bg-surface-container border-b border-outline-variant">
                    <tr>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Gateway</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Transactions</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse ($byGateway as $gatewayLabel => $data)
                        <tr>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $gatewayLabel }}</td>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ number_format($data['count']) }}</td>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface">Rp {{ number_format((float) $data['total_amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                                No completed transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
