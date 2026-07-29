@extends('layouts.app', ['topbarTitle' => 'Complete Your Payment'])

@section('title', 'Complete Your Payment')

@section('app-content')

    <div class="flex items-center justify-center py-space-xl"
        x-data="schoolPayment({
            confirmUrl: @js(route('school.payment.confirm', $transaction)),
            csrfToken: @js(csrf_token()),
            backUrl: @js($backUrl),
            initialResult: @js($initialResult),
            defaultChannel: @js($channels->first()?->value),
        })"
    >
        <div class="w-full max-w-[480px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <button type="button" @click="confirmBack" class="inline-flex items-center gap-space-xxs font-body-sm text-body-sm text-secondary hover:text-on-surface mb-space-lg">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </button>

            <div class="text-center mb-space-xl">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Complete Your Payment</h2>
            </div>

            <div class="space-y-space-lg">
                @if ($isTierChange)
                    <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                        <p class="font-body-sm text-body-sm text-secondary mb-space-xs">School</p>
                        <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->school->name }}</p>
                    </div>
                @else
                    <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                        <p class="font-body-sm text-body-sm text-secondary mb-space-xs">School</p>
                        <div class="flex items-center gap-space-sm">
                            @if ($transaction->registration_data['logo_path'] ?? null)
                                <img src="{{ $transaction->registration_data['logo_path'] }}" alt="{{ $transaction->registration_data['name'] }} logo" class="w-10 h-10 rounded-lg object-cover border border-outline-variant">
                            @endif
                            <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->registration_data['name'] }}</p>
                        </div>
                    </div>
                @endif

                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Plan</p>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->tier_name }}</p>
                    <p class="font-body-sm text-body-sm text-secondary">Billed {{ $transaction->billing_period }}</p>

                    @if ($isTierChange)
                        <div class="mt-space-md flex items-center justify-between border-t border-outline-variant pt-space-md">
                            <p class="font-label-md text-label-md text-on-surface">Total</p>
                            <p class="font-headline-sm text-headline-sm text-on-surface">Rp {{ number_format($transaction->amount, 0, '.', '.') }}</p>
                        </div>
                    @else
                        <dl class="mt-space-md space-y-space-xxs">
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>Subtotal</dt>
                                <dd>Rp {{ number_format($transaction->subtotal, 0, '.', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>VAT ({{ number_format($transaction->vat_rate * 100, 2) }}%)</dt>
                                <dd>Rp {{ number_format($transaction->vat_amount, 0, '.', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>Admin fee ({{ number_format($transaction->admin_fee_rate * ($transaction->admin_fee_type === \App\Enums\AdminFeeType::Percentage ? 100 : 1), 2) }}{{ $transaction->admin_fee_type === \App\Enums\AdminFeeType::Percentage ? '%' : '' }})</dt>
                                <dd>Rp {{ number_format($transaction->admin_fee_amount, 0, '.', '.') }}</dd>
                            </div>
                        </dl>
                        <div class="mt-space-md flex items-center justify-between border-t border-outline-variant pt-space-md">
                            <p class="font-label-md text-label-md text-on-surface">Total</p>
                            <p class="font-headline-sm text-headline-sm text-on-surface">Rp {{ number_format($transaction->amount, 0, '.', '.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Skeleton shown while the payment request is being created --}}
                <template x-if="loading">
                    <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-6 animate-pulse">
                        <div class="h-4 w-1/3 bg-outline-variant rounded mb-space-md mx-auto"></div>
                        <div class="h-24 w-24 bg-outline-variant rounded mx-auto mb-space-md"></div>
                        <div class="h-3 w-2/3 bg-outline-variant rounded mx-auto"></div>
                    </div>
                </template>

                {{-- Payment instructions, either rendered server-side (page reload / revisit) or filled in by JS after a channel is chosen --}}
                <template x-if="!loading && result && !switchingChannel">
                    <div>
                        <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-6 text-center">
                            <div class="flex items-center justify-center gap-space-xs mb-space-md">
                                <img :src="result.channel_logo" :alt="result.channel_label" class="w-8 h-8 rounded">
                                <p class="font-label-md text-label-md text-on-surface" x-text="result.channel_label"></p>
                            </div>

                            <template x-if="result.view_type === 'qris' && result.payment_instructions">
                                <div>
                                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Scan this QR code with any QRIS-supported app</p>
                                    <p class="font-mono text-body-sm text-on-surface break-all mt-space-sm" x-text="result.payment_instructions"></p>
                                </div>
                            </template>
                            <template x-if="result.view_type === 'virtual_account' && result.payment_instructions">
                                <div>
                                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Virtual Account Number</p>
                                    <p class="font-mono text-headline-sm text-on-surface tracking-wider" x-text="result.payment_instructions"></p>
                                </div>
                            </template>
                            <template x-if="result.view_type === 'retail' && result.payment_instructions">
                                <div>
                                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Payment Code</p>
                                    <p class="font-mono text-headline-sm text-on-surface tracking-wider" x-text="result.payment_instructions"></p>
                                </div>
                            </template>
                            <template x-if="result.view_type === 'ewallet' && result.payment_instructions">
                                <p class="text-secondary">Tap "Simulate Payment" below to open <span x-text="result.channel_label"></span> and complete the payment.</p>
                            </template>
                            <template x-if="!result.payment_instructions">
                                <p class="text-secondary">Waiting for payment instructions from the gateway.</p>
                            </template>
                        </div>

                        {{-- How to pay --}}
                        <template x-if="result.guide_steps && result.guide_steps.length">
                            <div class="mt-space-md rounded-lg border border-outline-variant px-4 py-3">
                                <p class="font-label-md text-label-md text-on-surface mb-space-sm">How to pay</p>
                                <ol class="list-decimal list-inside space-y-space-xxs">
                                    <template x-for="step in result.guide_steps" :key="step">
                                        <li class="font-body-sm text-body-sm text-secondary" x-text="step"></li>
                                    </template>
                                </ol>
                            </div>
                        </template>

                        {{-- Sandbox-only: e-wallet channels have no real Xendit simulate endpoint —
                             "simulating" here just opens the actual e-wallet deeplink on this same page,
                             instead of the auto-redirect used before. --}}
                        <template x-if="result.is_sandbox && result.view_type === 'ewallet' && result.payment_instructions">
                            <div class="mt-space-md">
                                <button type="button" @click="openEwalletLink" class="w-full h-[40px] rounded-lg border border-primary text-primary font-label-md text-label-md hover:bg-primary/5 flex items-center justify-center gap-space-xs">
                                    Simulate Payment (Sandbox)
                                </button>
                            </div>
                        </template>

                        {{-- Sandbox-only: simulate the payment instead of waiting on the real gateway --}}
                        <template x-if="result.is_sandbox && result.view_type !== 'ewallet' && result.supports_simulation">
                            <div class="mt-space-md">
                                <button type="button" @click="simulatePayment" :disabled="simulating" class="w-full h-[40px] rounded-lg border border-primary text-primary font-label-md text-label-md hover:bg-primary/5 disabled:opacity-50 flex items-center justify-center gap-space-xs">
                                    <svg x-show="simulating" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="simulating ? 'Simulating payment...' : 'Simulate Payment (Sandbox)'"></span>
                                </button>
                                <p x-show="simulateMessage" x-text="simulateMessage" class="text-center font-body-sm text-body-sm text-secondary mt-space-xs"></p>
                            </div>
                        </template>

                        @if ($channels->count() > 1)
                            <button type="button" @click="switchingChannel = true; channel = result.channel" class="w-full text-center font-label-md text-label-md text-primary hover:underline mt-space-md">
                                Change payment method
                            </button>
                        @endif

                        <p class="text-center font-body-sm text-body-sm text-secondary mt-space-md">
                            @if ($isTierChange)
                                Your subscription will be updated automatically once payment is confirmed.
                            @else
                                Your school will be created automatically once payment is confirmed.
                            @endif
                        </p>
                    </div>
                </template>

                <template x-if="!loading && (!result || switchingChannel)">
                    <form @submit.prevent="submitChannel" class="space-y-space-md">
                        @if ($channels->isNotEmpty())
                            <div>
                                <p class="font-label-md text-label-md text-on-surface mb-space-sm">Choose a payment method</p>
                                <div class="space-y-space-xs">
                                    @foreach ($channels->take(3) as $channel)
                                        <label class="flex items-center gap-space-sm rounded-lg border border-outline-variant px-4 py-3 cursor-pointer hover:bg-surface-container-lowest has-[:checked]:border-primary has-[:checked]:bg-surface-container-lowest">
                                            <input type="radio" name="channel" value="{{ $channel->value }}" x-model="channel" {{ $loop->first ? 'checked' : '' }} class="accent-primary" required>
                                            <img src="{{ $channel->logoUrl() }}" alt="{{ $channel->label() }}" class="w-8 h-8 rounded" loading="lazy">
                                            <span class="font-body-md text-body-md text-on-surface">{{ $channel->label() }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                @if ($channels->count() > 3)
                                    {{-- CSS grid-rows trick animates height without a plugin or JS measuring --}}
                                    <div class="grid transition-[grid-template-rows] duration-300 ease-in-out" :class="showAllChannels ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                                        <div class="overflow-hidden">
                                            <div class="space-y-space-xs pt-space-xs">
                                                @foreach ($channels->skip(3) as $channel)
                                                    <label class="flex items-center gap-space-sm rounded-lg border border-outline-variant px-4 py-3 cursor-pointer hover:bg-surface-container-lowest has-[:checked]:border-primary has-[:checked]:bg-surface-container-lowest">
                                                        <input type="radio" name="channel" value="{{ $channel->value }}" x-model="channel" class="accent-primary" required>
                                                        <img src="{{ $channel->logoUrl() }}" alt="{{ $channel->label() }}" class="w-8 h-8 rounded" loading="lazy">
                                                        <span class="font-body-md text-body-md text-on-surface">{{ $channel->label() }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" @click="showAllChannels = !showAllChannels" class="w-full flex items-center justify-center gap-space-xxs text-center font-label-md text-label-md text-primary hover:underline mt-space-sm">
                                        <span x-text="showAllChannels ? 'Show fewer options' : 'Show {{ $channels->count() - 3 }} more options'"></span>
                                        <svg class="w-3 h-3 transition-transform duration-300" :class="showAllChannels ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endif

                        <p x-show="errorMessage" x-text="errorMessage" class="font-body-sm text-body-sm text-error"></p>

                        {{-- Sticky so Cancel/Confirm stay reachable while the channel list scrolls --}}
                        <div class="sticky bottom-0 -mx-space-xl px-space-xl pt-space-sm pb-space-xs bg-surface border-t border-outline-variant flex gap-space-sm">
                            <template x-if="result">
                                <button type="button" @click="switchingChannel = false; errorMessage = ''" class="flex-1 h-[44px] rounded-lg border border-outline-variant font-label-md text-label-md text-on-surface hover:bg-surface-container-lowest">
                                    Cancel
                                </button>
                            </template>
                            <button type="submit" class="flex-1 h-[44px] bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
                                Confirm Payment
                            </button>
                        </div>
                    </form>
                    <p class="text-center font-body-sm text-body-sm text-secondary">
                        @if ($isTierChange)
                            Your subscription will be updated once payment is confirmed.
                        @else
                            Your school will be created once payment is confirmed.
                        @endif
                    </p>
                </template>
            </div>
        </div>

        {{-- Back-navigation warning modal --}}
        <div x-show="showBackModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-gutter">
            <div class="absolute inset-0 bg-black/50" @click="showBackModal = false"></div>
            <div class="relative w-full max-w-[400px] bg-surface rounded-xl p-space-lg border border-outline-variant shadow-xl">
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-space-xs">Leave this page?</h3>
                <p class="font-body-sm text-body-sm text-secondary mb-space-lg">
                    @if ($isTierChange)
                        Going back will cancel this upgrade. You can try paying again later by picking this transaction from
                        <a href="{{ route('transactions.index') }}" class="text-primary hover:underline">Transactions</a>.
                    @else
                        Going back will discard this checkout session, but your school details are saved. You can try paying again later by picking this transaction from
                        <a href="{{ route('transactions.index') }}" class="text-primary hover:underline">Transactions</a>.
                    @endif
                </p>
                <div class="flex gap-space-sm">
                    <button type="button" @click="showBackModal = false" class="flex-1 h-[40px] rounded-lg border border-outline-variant font-label-md text-label-md text-on-surface hover:bg-surface-container-lowest">
                        Stay
                    </button>
                    <a :href="backUrlValue" class="flex-1 h-[40px] leading-[40px] text-center rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-on-primary-fixed-variant">
                        Leave
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function schoolPayment({ confirmUrl, csrfToken, backUrl, initialResult, defaultChannel }) {
            return {
                loading: false,
                result: initialResult,
                errorMessage: '',
                channel: defaultChannel || null,
                switchingChannel: false,
                showAllChannels: false,
                showBackModal: false,
                backUrlValue: backUrl,
                simulating: false,
                simulateMessage: '',
                eventSource: null,

                init() {
                    if (this.result && this.result.is_sandbox) {
                        this.startWatching();
                    }
                },

                confirmBack() {
                    this.showBackModal = true;
                },

                async submitChannel() {
                    const channel = this.channel;
                    this.errorMessage = '';
                    this.loading = true;
                    this.result = null;
                    this.stopWatching();

                    try {
                        const response = await fetch(confirmUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ channel }),
                        });

                        // A server error can come back as an HTML error page
                        // instead of JSON — parse defensively so that case
                        // still surfaces a useful message instead of a
                        // generic "something went wrong".
                        let data;
                        try {
                            data = await response.json();
                        } catch (parseError) {
                            this.loading = false;
                            this.errorMessage = `Server error (${response.status}). Please try again or contact support.`;

                            return;
                        }

                        if (!response.ok) {
                            this.loading = false;
                            this.errorMessage = data.message || 'Unable to initiate payment. Please try again.';

                            return;
                        }

                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;

                            return;
                        }

                        this.result = data;
                        this.loading = false;
                        this.switchingChannel = false;
                        this.simulateMessage = '';

                        if (this.result.is_sandbox) {
                            this.startWatching();
                        }
                    } catch (e) {
                        this.loading = false;
                        this.errorMessage = 'Something went wrong. Please check your connection and try again.';
                    }
                },

                openEwalletLink() {
                    if (!this.result || !this.result.payment_instructions) return;

                    window.open(this.result.payment_instructions, '_blank', 'noopener');
                },

                async simulatePayment() {
                    if (!this.result || !this.result.simulate_url) return;

                    this.simulating = true;
                    this.simulateMessage = '';

                    try {
                        const response = await fetch(this.result.simulate_url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.simulating = false;
                            this.simulateMessage = data.message || 'Unable to simulate payment.';

                            return;
                        }

                        this.simulateMessage = 'Simulation triggered — waiting for confirmation...';
                        this.startWatching();
                    } catch (e) {
                        this.simulating = false;
                        this.simulateMessage = 'Something went wrong. Please try again.';
                    }
                },

                // Server-Sent Events instead of client-side interval polling:
                // the server pushes a "completed" event the moment the
                // webhook lands, instead of the browser asking on a timer.
                // Each connection self-closes after ~10s (see
                // SchoolPaymentController::stream) and EventSource
                // auto-reconnects, so this keeps watching until stopped.
                startWatching() {
                    if (!this.result || !this.result.stream_url || this.eventSource) return;

                    this.eventSource = new EventSource(this.result.stream_url);

                    this.eventSource.addEventListener('completed', (e) => {
                        const data = JSON.parse(e.data);
                        this.stopWatching();
                        window.location.href = data.redirect_url;
                    });

                    this.eventSource.addEventListener('failed', () => {
                        this.stopWatching();
                        this.simulateMessage = 'Payment failed. Please try again.';
                    });

                    // "timeout" just means this connection's ~10s window
                    // elapsed with nothing to report — EventSource reconnects
                    // on its own, nothing to do here.
                },

                stopWatching() {
                    if (this.eventSource) {
                        this.eventSource.close();
                        this.eventSource = null;
                    }
                    this.simulating = false;
                },
            };
        }
    </script>

@endsection
