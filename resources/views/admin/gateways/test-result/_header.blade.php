<div class="mb-8">
    <a href="{{ route('admin.gateways.index') }}" class="text-primary text-body-sm font-medium hover:underline">
        &larr; Back to Gateways
    </a>
    <h1 class="font-headline-md text-headline-md text-on-surface mt-4">Test Payment Result</h1>
    <p class="text-body-md text-on-surface-variant mt-1">
        {{ $gateway->paymentGatewayType->label }} &middot; {{ $channel?->label() ?? 'Default' }}
    </p>
</div>

<div class="bg-surface border border-outline rounded-lg p-6 mb-6">
    <dl class="grid grid-cols-2 gap-4 text-body-sm">
        <div>
            <dt class="text-on-surface-variant">Status</dt>
            <dd class="font-medium text-on-surface mt-1 capitalize">{{ str_replace('_', ' ', $response['status'] ?? 'unknown') }}</dd>
        </div>
        <div>
            <dt class="text-on-surface-variant">Amount</dt>
            <dd class="font-medium text-on-surface mt-1">{{ $response['currency'] ?? 'IDR' }} {{ number_format($response['amount'] ?? 0) }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-on-surface-variant">Transaction ID</dt>
            <dd class="font-medium text-on-surface mt-1 break-all">{{ $response['transaction_id'] ?? '-' }}</dd>
        </div>
    </dl>
</div>
