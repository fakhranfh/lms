@section('title', 'Transaction Detail')

@php
    $statusClasses = [
        'pending' => 'bg-tertiary-container text-on-tertiary-container',
        'completed' => 'bg-primary-container text-on-primary-container',
        'failed' => 'bg-error-container text-on-error-container',
        'refunded' => 'bg-outline-variant text-on-surface',
    ];
@endphp

<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Transaction Detail</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">{{ $transaction->transaction_id }}</p>
        </div>
        <a href="{{ route('admin.transactions.index') }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
            Back to Transactions
        </a>
    </div>

    <div class="bg-surface rounded-lg border border-outline-variant p-space-lg space-y-space-md">
        <h2 class="font-title-md text-title-md text-on-surface">Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md text-body-sm">
            <div><span class="text-on-surface-variant">Transaction ID:</span> {{ $transaction->transaction_id }}</div>
            <div>
                <span class="text-on-surface-variant">Status:</span>
                <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm capitalize {{ $statusClasses[$transaction->status->value] ?? 'bg-outline-variant text-on-surface' }}">
                    {{ $transaction->status->label() }}
                </span>
            </div>
            <div><span class="text-on-surface-variant">Type:</span> {{ $transaction->transaction_type?->label() }}</div>
            <div><span class="text-on-surface-variant">Gateway:</span> {{ $transaction->paymentGateway?->paymentGatewayType?->label ?? '—' }}</div>
            <div><span class="text-on-surface-variant">Channel:</span> {{ $transaction->channel ?? '—' }}</div>
            <div><span class="text-on-surface-variant">Initiated by:</span> {{ $transaction->initiatedBy?->email ?? '—' }}</div>
            <div><span class="text-on-surface-variant">Date:</span> {{ $transaction->created_at_display?->format('M j, Y g:i A') }}</div>
        </div>
    </div>

    <div class="bg-surface rounded-lg border border-outline-variant p-space-lg space-y-space-md">
        <h2 class="font-title-md text-title-md text-on-surface">Payment Breakdown</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md text-body-sm">
            <div><span class="text-on-surface-variant">Subtotal:</span> Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</div>
            <div><span class="text-on-surface-variant">VAT:</span> Rp {{ number_format((float) $transaction->vat_amount, 0, ',', '.') }}</div>
            <div><span class="text-on-surface-variant">Admin fee:</span> Rp {{ number_format((float) $transaction->admin_fee_amount, 0, ',', '.') }}</div>
            <div><span class="text-on-surface-variant">Total:</span> Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</div>
        </div>
    </div>

    @switch($transaction->transaction_type)
        @case(\App\Enums\TransactionType::TierPurchase)
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg space-y-space-md">
                <h2 class="font-title-md text-title-md text-on-surface">Tier Purchase Details</h2>

                <div class="flex items-center gap-space-md">
                    @if ($transaction->school?->logo_path)
                        <img src="{{ $transaction->school->logo_path }}" alt="{{ $transaction->school->name }} logo" class="w-12 h-12 rounded-lg object-cover border border-outline-variant">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center border border-outline-variant">
                            <span class="material-symbols-outlined text-on-surface-variant">school</span>
                        </div>
                    @endif
                    <div>
                        <div class="text-body-md text-on-surface">{{ $transaction->school?->name ?? '—' }}</div>
                        <div class="text-body-sm text-on-surface-variant">{{ $transaction->school?->domain ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md text-body-sm">
                    <div><span class="text-on-surface-variant">Tier:</span> {{ $transaction->tier_name ?? '—' }}</div>
                    <div><span class="text-on-surface-variant">Billing period:</span> {{ $transaction->billing_period ?? '—' }}</div>
                    <div><span class="text-on-surface-variant">Change type:</span> {{ $transaction->change_type?->label() ?? '—' }}</div>
                    <div><span class="text-on-surface-variant">From tier:</span> {{ $transaction->detail?->fromTier?->name ?? '—' }}</div>
                    <div><span class="text-on-surface-variant">Proration amount:</span> {{ $transaction->proration_amount !== null ? 'Rp '.number_format((float) $transaction->proration_amount, 0, ',', '.') : '—' }}</div>
                </div>
            </div>
        @break

        @default
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
                <p class="text-body-sm text-on-surface-variant">No additional details for this transaction type.</p>
            </div>
    @endswitch
</div>
