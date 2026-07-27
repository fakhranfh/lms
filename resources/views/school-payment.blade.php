@extends('master')

@section('title', 'Complete Your Payment')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    @include('partials.topbar')

    <main class="flex flex-1 items-center justify-center p-gutter">
        <div class="w-full max-w-[480px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <div class="text-center mb-space-xl">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Complete Your Payment</h2>
                <p class="font-body-md text-body-md text-secondary">Step 3 of 3</p>
            </div>

            <div class="space-y-space-lg">
                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">School</p>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->registration_data['name'] }}</p>
                </div>

                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Plan</p>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->metadata['tier_name'] }}</p>
                    <p class="font-body-sm text-body-sm text-secondary">Billed {{ $transaction->metadata['billing_period'] }}</p>

                    <dl class="mt-space-md space-y-space-xxs">
                        <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                            <dt>Subtotal</dt>
                            <dd>Rp {{ number_format($transaction->metadata['subtotal'], 0, '.', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                            <dt>VAT ({{ number_format($transaction->metadata['vat_rate'] * 100, 2) }}%)</dt>
                            <dd>Rp {{ number_format($transaction->metadata['vat_amount'], 0, '.', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                            <dt>Admin fee ({{ number_format($transaction->metadata['admin_fee_rate'] * 100, 2) }}%)</dt>
                            <dd>Rp {{ number_format($transaction->metadata['admin_fee_amount'], 0, '.', '.') }}</dd>
                        </div>
                    </dl>
                    <div class="mt-space-md flex items-center justify-between border-t border-outline-variant pt-space-md">
                        <p class="font-label-md text-label-md text-on-surface">Total</p>
                        <p class="font-headline-sm text-headline-sm text-on-surface">Rp {{ number_format($transaction->amount, 0, '.', '.') }}</p>
                    </div>
                </div>

                <div class="space-y-space-md">
                    <form method="POST" action="{{ route('school.payment.confirm', $transaction) }}">
                        @csrf
                        <button type="submit" class="w-full h-[44px] bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
                            Confirm Payment
                        </button>
                    </form>
                    <p class="text-center font-body-sm text-body-sm text-secondary">Your school will be created once payment is confirmed.</p>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')

@endsection
