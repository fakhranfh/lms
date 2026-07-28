<div class="mb-8">
    <a href="{{ route('admin.gateways.index') }}" class="text-primary text-body-sm font-medium hover:underline">
        &larr; Back to Gateways
    </a>
    <h1 class="font-headline-md text-headline-md text-on-surface mt-4">Test Payment Result</h1>
    <p class="text-body-md text-on-surface-variant mt-1">
        {{ $gateway->paymentGatewayType->label }} &middot; {{ $channel?->label() ?? 'Default' }}
    </p>
</div>

@if ($message = Session::get('success'))
    <div class="mb-6 p-4 bg-success-container border border-success rounded-lg text-on-success-container">
        {{ $message }}
    </div>
@endif

@if ($message = Session::get('error'))
    <div class="mb-6 p-4 bg-error-container border border-error rounded-lg text-error">
        {{ $message }}
    </div>
@endif

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

@if ($channel?->supportsSimulation())
    <form action="{{ route('admin.gateways.test-result.simulate', $gateway) }}" method="POST" class="mb-6" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <button type="submit" :disabled="loading" class="w-full px-6 py-3 bg-secondary text-on-secondary rounded-lg font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2">
            <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span x-text="loading ? 'Simulating...' : 'Simulate Payment (Test Mode)'"></span>
        </button>
        <p class="text-body-sm text-on-surface-variant mt-2 text-center">
            Marks this test transaction as paid via Xendit's test-mode simulation endpoint.
        </p>
    </form>
@elseif ($channel)
    <p class="text-body-sm text-on-surface-variant mb-6 text-center">
        Xendit's payment simulation isn't available for {{ $channel->label() }} — approve it through the {{ $channel->label() }} app/redirect above instead.
    </p>
@endif
