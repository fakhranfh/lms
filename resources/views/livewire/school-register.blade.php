@section('title', 'Register your school')

<div class="flex min-h-screen flex-col">
    @include('partials.topbar')

    <main class="flex flex-1 items-center justify-center p-gutter">
        <div class="w-full max-w-[480px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div class="text-center mb-space-xl">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Register your school</h2>
            <p class="font-body-md text-body-md text-secondary">Step 2 of 2</p>
        </div>

        @if (session('status'))
            <div class="mb-space-md rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-700 text-body-sm font-body-sm">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-space-md">
            <div class="space-y-space-xs">
                <label class="block font-label-md text-label-md text-on-surface" for="name">School Name</label>
                <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('name') border-error @enderror" id="name" wire:model="name" placeholder="My School" required>
                @error('name')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-space-xs" x-data="{ previewUrl: null }">
                <span class="block font-label-md text-label-md text-on-surface">School Logo</span>
                <div class="flex items-center gap-space-md">
                    <label for="logo" class="w-16 h-16 rounded-lg border border-outline-variant bg-surface-container-lowest overflow-hidden flex items-center justify-center cursor-pointer flex-shrink-0">
                        <img x-show="previewUrl" :src="previewUrl" alt="Logo preview" class="w-full h-full object-cover">
                        <span x-show="!previewUrl" class="material-symbols-outlined text-secondary/60 text-[24px]">add_photo_alternate</span>
                    </label>
                    <div>
                        <label for="logo" class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors cursor-pointer">Upload logo</label>
                        <p class="font-body-sm text-body-sm text-secondary">JPG, PNG, GIF or WEBP. Max 5MB. Optional.</p>
                    </div>
                    <input
                        type="file"
                        id="logo"
                        wire:model="logo"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="hidden"
                        @change="previewUrl = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null"
                    >
                </div>
                @error('logo')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-space-xs">
                <label class="block font-label-md text-label-md text-on-surface" for="tierId">Plan</label>
                <select class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('tierId') border-error @enderror" id="tierId" wire:model.live="tierId" required>
                    <option value="" disabled>Select a plan</option>
                    @foreach ($tiers as $tier)
                        <option value="{{ $tier->id }}">
                            {{ $tier->name }} &mdash; {{ (float) $tier->price === 0.0 ? 'Free' : 'Rp '.number_format($tier->price, 0, '.', '.').' / '.strtolower($tier->billing_period->label()) }}
                        </option>
                    @endforeach
                </select>
                @error('tierId')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
                <p class="font-body-sm text-body-sm text-secondary">You can change your plan anytime after registering.</p>
            </div>

            <div class="space-y-space-xs" x-data="{ domainType: @entangle('domainType').defer }">
                <span class="block font-label-md text-label-md text-on-surface">Domain</span>
                <div class="grid grid-cols-2 gap-space-sm">
                    <label class="flex items-center gap-space-xs h-[44px] px-3 rounded-lg border cursor-pointer transition-colors" :class="domainType === 'subdomain' ? 'border-primary ring-1 ring-primary' : 'border-outline-variant'">
                        <input type="radio" x-model="domainType" value="subdomain" class="accent-primary">
                        <span class="font-body-md text-body-md text-on-surface">Use a subdomain</span>
                    </label>
                    <label class="flex items-center gap-space-xs h-[44px] px-3 rounded-lg border cursor-pointer transition-colors" :class="domainType === 'custom' ? 'border-primary ring-1 ring-primary' : 'border-outline-variant'">
                        <input type="radio" x-model="domainType" value="custom" class="accent-primary">
                        <span class="font-body-md text-body-md text-on-surface">Use my own domain</span>
                    </label>
                </div>

                <div x-show="domainType === 'subdomain'" x-cloak>
                    <div class="space-y-space-xs">
                        <label class="block font-label-md text-label-md text-on-surface" for="subdomain">Subdomain</label>
                        <div class="flex items-center">
                            <input class="w-full h-[44px] px-3 rounded-l-lg border border-r-0 border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('subdomain') border-error @enderror" id="subdomain" wire:model.live.debounce.500ms="subdomain" placeholder="myschool" required>
                            <span class="h-[44px] flex items-center px-3 rounded-r-lg border border-outline-variant bg-surface-container-low text-secondary font-body-md text-body-md whitespace-nowrap">.{{ config('app.domain') }}</span>
                        </div>
                        @error('subdomain')
                            <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                        @enderror
                        @if ($subdomain && ! $errors->has('subdomain'))
                            <p class="text-success text-body-sm font-body-sm mt-space-xs">{{ $subdomain }}.{{ config('app.domain') }} is available.</p>
                        @endif
                    </div>
                </div>

                <div x-show="domainType === 'custom'" x-cloak>
                    <div class="space-y-space-xs">
                        <label class="block font-label-md text-label-md text-on-surface" for="customDomain">Your Domain</label>
                        <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('customDomain') border-error @enderror" id="customDomain" wire:model.live.debounce.500ms="customDomain" placeholder="lms.yourschool.com" required>
                        @error('customDomain')
                            <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                        @enderror
                        <p class="font-body-sm text-body-sm text-secondary mt-space-xs">After registering, we'll show you the DNS records to point this domain to your school.</p>
                    </div>
                </div>
            </div>

            @php
                $selectedTier = $tiers->firstWhere('id', (int) $tierId);
                $subtotal = $selectedTier ? (float) $selectedTier->price : 0.0;
                $vatRate = config('billing.vat_rate');
                $adminFeeRate = config('billing.admin_fee_rate');
                $vatAmount = $subtotal * $vatRate;
                $adminFeeAmount = $subtotal * $adminFeeRate;
                $total = $subtotal + $vatAmount + $adminFeeAmount;
                $formatRp = fn (float $amount) => 'Rp '.number_format($amount, 0, '.', '.');
            @endphp
            @if ($selectedTier)
                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="font-label-md text-label-md text-on-surface">{{ $selectedTier->name }} plan</p>
                        <p class="font-body-sm text-body-sm text-secondary">Billed {{ strtolower($selectedTier->billing_period->label()) }}</p>
                    </div>

                    @if ($subtotal === 0.0)
                        <p class="mt-space-sm font-headline-sm text-headline-sm text-on-surface">Free</p>
                    @else
                        <dl class="mt-space-sm space-y-space-xxs">
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>Subtotal</dt>
                                <dd>{{ $formatRp($subtotal) }}</dd>
                            </div>
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>VAT ({{ rtrim(rtrim(number_format($vatRate * 100, 2), '0'), '.') }}%)</dt>
                                <dd>{{ $formatRp($vatAmount) }}</dd>
                            </div>
                            <div class="flex items-center justify-between font-body-sm text-body-sm text-secondary">
                                <dt>Admin fee ({{ rtrim(rtrim(number_format($adminFeeRate * 100, 2), '0'), '.') }}%)</dt>
                                <dd>{{ $formatRp($adminFeeAmount) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-space-sm flex items-center justify-between border-t border-outline-variant pt-space-sm">
                            <p class="font-label-md text-label-md text-on-surface">Total</p>
                            <p class="font-headline-sm text-headline-sm text-on-surface">{{ $formatRp($total) }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <button wire:loading.attr="disabled" wire:target="save" class="w-full h-[44px] mt-space-lg bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all disabled:opacity-60 disabled:cursor-not-allowed" type="submit">
                <span wire:loading.remove wire:target="save">Register School</span>
                <span wire:loading wire:target="save">Registering...</span>
            </button>
        </form>
        </div>
    </main>

    @include('partials.footer')
</div>
