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
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $school->name }}</p>
                </div>

                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Plan</p>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $school->tier->name }}</p>

                    @if ((float) $school->tier->price > 0)
                        @php
                            $subtotal = (float) $school->tier->price;
                            $vat = $subtotal * config('billing.vat_rate');
                            $adminFee = $subtotal * config('billing.admin_fee_rate');
                            $total = $subtotal + $vat + $adminFee;
                            $vatPercent = config('billing.vat_rate') * 100;
                            $adminFeePercent = config('billing.admin_fee_rate') * 100;
                        @endphp
                        <dl class="mt-space-md space-y-space-xxs">
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>Subtotal</dt>
                                <dd>Rp {{ number_format($subtotal, 0, '.', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>VAT ({{ number_format($vatPercent, 2) }}%)</dt>
                                <dd>Rp {{ number_format($vat, 0, '.', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>Admin fee ({{ number_format($adminFeePercent, 2) }}%)</dt>
                                <dd>Rp {{ number_format($adminFee, 0, '.', '.') }}</dd>
                            </div>
                        </dl>
                        <div class="mt-space-md flex items-center justify-between border-t border-outline-variant pt-space-md">
                            <p class="font-label-md text-label-md text-on-surface">Total</p>
                            <p class="font-headline-sm text-headline-sm text-on-surface">Rp {{ number_format($total, 0, '.', '.') }}</p>
                        </div>
                    @else
                        <p class="mt-space-md font-headline-sm text-headline-sm text-on-surface">Free</p>
                    @endif
                </div>

                <div class="space-y-space-md">
                    <form method="GET" action="{{ route('manage.schools.index') }}">
                        <button type="submit" class="w-full h-[44px] bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
                            Proceed to Dashboard
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')

@endsection
