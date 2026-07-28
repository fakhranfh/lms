@extends('master')

@section('title', 'Complete Your Payment')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    @include('partials.topbar')

    <main class="flex flex-1 items-center justify-center p-gutter"
        x-data="schoolPayment({
            confirmUrl: @js(route('school.payment.confirm', $transaction)),
            csrfToken: @js(csrf_token()),
            backUrl: @js(route('get-started.school'.\App\Support\RootDomains::currentSuffix())),
            initialResult: @js($selectedChannel ? [
                'channel' => $selectedChannel->value,
                'channel_label' => $selectedChannel->label(),
                'channel_logo' => $selectedChannel->logoUrl(),
                'view_type' => $selectedChannel->viewType(),
                'payment_instructions' => $transaction->payment_instructions,
            ] : null),
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
                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">School</p>
                    <div class="flex items-center gap-space-sm">
                        @if ($transaction->registration_data['logo_path'] ?? null)
                            <img src="{{ $transaction->registration_data['logo_path'] }}" alt="{{ $transaction->registration_data['name'] }} logo" class="w-10 h-10 rounded-lg object-cover border border-outline-variant">
                        @endif
                        <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->registration_data['name'] }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <p class="font-body-sm text-body-sm text-secondary mb-space-xs">Plan</p>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $transaction->tier_name }}</p>
                    <p class="font-body-sm text-body-sm text-secondary">Billed {{ $transaction->billing_period }}</p>

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
                            <template x-if="result.view_type !== 'qris' && result.view_type !== 'virtual_account' && result.view_type !== 'retail'">
                                <p class="text-secondary">Waiting for payment instructions from the gateway.</p>
                            </template>
                        </div>

                        @if ($channels->count() > 1)
                            <button type="button" @click="switchingChannel = true; channel = result.channel" class="w-full text-center font-label-md text-label-md text-primary hover:underline mt-space-md">
                                Change payment method
                            </button>
                        @endif

                        <p class="text-center font-body-sm text-body-sm text-secondary mt-space-md">Your school will be created automatically once payment is confirmed.</p>
                    </div>
                </template>

                <template x-if="!loading && (!result || switchingChannel)">
                    <form @submit.prevent="submitChannel" class="space-y-space-md">
                        @if ($channels->isNotEmpty())
                            <div>
                                <p class="font-label-md text-label-md text-on-surface mb-space-sm">Choose a payment method</p>
                                <div class="space-y-space-xs">
                                    @foreach ($channels as $channel)
                                        <label class="flex items-center gap-space-sm rounded-lg border border-outline-variant px-4 py-3 cursor-pointer hover:bg-surface-container-lowest has-[:checked]:border-primary has-[:checked]:bg-surface-container-lowest">
                                            <input type="radio" name="channel" value="{{ $channel->value }}" x-model="channel" {{ $loop->first ? 'checked' : '' }} class="accent-primary" required>
                                            <img src="{{ $channel->logoUrl() }}" alt="{{ $channel->label() }}" class="w-8 h-8 rounded" loading="lazy">
                                            <span class="font-body-md text-body-md text-on-surface">{{ $channel->label() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <p x-show="errorMessage" x-text="errorMessage" class="font-body-sm text-body-sm text-error"></p>

                        <div class="flex gap-space-sm">
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
                    <p class="text-center font-body-sm text-body-sm text-secondary">Your school will be created once payment is confirmed.</p>
                </template>
            </div>
        </div>

        {{-- Back-navigation warning modal --}}
        <div x-show="showBackModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-gutter">
            <div class="absolute inset-0 bg-black/50" @click="showBackModal = false"></div>
            <div class="relative w-full max-w-[400px] bg-surface rounded-xl p-space-lg border border-outline-variant shadow-xl">
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-space-xs">Leave this page?</h3>
                <p class="font-body-sm text-body-sm text-secondary mb-space-lg">Going back will discard this checkout session. You'll need to fill in your school details again.</p>
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
    </main>

    @include('partials.footer')

    <script>
        function schoolPayment({ confirmUrl, csrfToken, backUrl, initialResult, defaultChannel }) {
            return {
                loading: false,
                result: initialResult,
                errorMessage: '',
                channel: defaultChannel || null,
                switchingChannel: false,
                showBackModal: false,
                backUrlValue: backUrl,

                confirmBack() {
                    this.showBackModal = true;
                },

                async submitChannel() {
                    const channel = this.channel;
                    this.errorMessage = '';
                    this.loading = true;
                    this.result = null;

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

                        const data = await response.json();

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
                    } catch (e) {
                        this.loading = false;
                        this.errorMessage = 'Something went wrong. Please try again.';
                    }
                },
            };
        }
    </script>

@endsection
