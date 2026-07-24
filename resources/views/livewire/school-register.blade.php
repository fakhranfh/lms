@section('title', 'Register your school')

<div class="w-full min-h-screen flex items-center justify-center">
<div class="w-full max-w-[480px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
    @if ($registeredUrl)
        <div class="text-center mb-space-lg">
            <span class="material-symbols-outlined text-success text-[40px]" data-weight="fill">check_circle</span>
            <h2 class="font-headline-md text-headline-md text-on-surface mt-space-xs mb-space-xxs">School registered!</h2>
            <p class="font-body-md text-body-md text-secondary">Your school domain is <strong>{{ $registeredDomain }}</strong>.</p>
        </div>

        @if ($domainType === 'custom')
            <div class="mb-space-lg rounded-lg border border-outline-variant bg-surface-container-lowest p-space-md space-y-space-sm">
                <p class="font-label-md text-label-md text-on-surface">Connect your domain</p>
                <p class="font-body-sm text-body-sm text-secondary">Before {{ $registeredDomain }} works, point it to this application using one of the following DNS records at your domain registrar:</p>
                <ul class="font-body-sm text-body-sm text-on-surface list-disc list-inside space-y-space-xxs">
                    <li><strong>CNAME</strong> record: host <code>{{ $registeredDomain }}</code> &rarr; <code>{{ config('app.domain') }}</code></li>
                    <li>or an <strong>A</strong> record pointing to your server's IP address</li>
                </ul>
                <p class="font-body-sm text-body-sm text-secondary">DNS changes can take a few minutes to a few hours to propagate. Once it resolves, visit the link below to create your admin account.</p>
            </div>
        @endif

        <a href="{{ $registeredUrl }}" class="w-full h-[44px] flex items-center justify-center bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
            Go to {{ $registeredUrl }}
        </a>
    @else
        <div class="text-center mb-space-xl">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Register your school</h2>
        </div>

        <form wire:submit="save" class="space-y-space-md">
            <div class="space-y-space-xs">
                <label class="block font-label-md text-label-md text-on-surface" for="name">School Name</label>
                <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('name') border-error @enderror" id="name" wire:model="name" placeholder="My School" required>
                @error('name')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-space-xs">
                <span class="block font-label-md text-label-md text-on-surface">School Logo</span>
                <div class="flex items-center gap-space-md">
                    <label for="logo" class="w-16 h-16 rounded-lg border border-outline-variant bg-surface-container-lowest overflow-hidden flex items-center justify-center cursor-pointer flex-shrink-0">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="w-full h-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-secondary/60 text-[24px]">add_photo_alternate</span>
                        @endif
                    </label>
                    <div>
                        <label for="logo" class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors cursor-pointer">Upload logo</label>
                        <p class="font-body-sm text-body-sm text-secondary">JPG, PNG, GIF or WEBP. Max 5MB. Optional.</p>
                    </div>
                    <input type="file" id="logo" wire:model="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                </div>
                @error('logo')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-space-xs">
                <span class="block font-label-md text-label-md text-on-surface">Domain</span>
                <div class="grid grid-cols-2 gap-space-sm">
                    <label class="flex items-center gap-space-xs h-[44px] px-3 rounded-lg border cursor-pointer transition-colors {{ $domainType === 'subdomain' ? 'border-primary ring-1 ring-primary' : 'border-outline-variant' }}">
                        <input type="radio" wire:model.live="domainType" value="subdomain" class="accent-primary">
                        <span class="font-body-md text-body-md text-on-surface">Use a subdomain</span>
                    </label>
                    <label class="flex items-center gap-space-xs h-[44px] px-3 rounded-lg border cursor-pointer transition-colors {{ $domainType === 'custom' ? 'border-primary ring-1 ring-primary' : 'border-outline-variant' }}">
                        <input type="radio" wire:model.live="domainType" value="custom" class="accent-primary">
                        <span class="font-body-md text-body-md text-on-surface">Use my own domain</span>
                    </label>
                </div>
            </div>

            @if ($domainType === 'subdomain')
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
            @else
                <div class="space-y-space-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="customDomain">Your Domain</label>
                    <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('customDomain') border-error @enderror" id="customDomain" wire:model.live.debounce.500ms="customDomain" placeholder="lms.yourschool.com" required>
                    @error('customDomain')
                        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                    @enderror
                    <p class="font-body-sm text-body-sm text-secondary mt-space-xs">After registering, we'll show you the DNS records to point this domain to your school.</p>
                </div>
            @endif

            <button wire:loading.attr="disabled" wire:target="save" class="w-full h-[44px] mt-space-lg bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all disabled:opacity-60 disabled:cursor-not-allowed" type="submit">
                <span wire:loading.remove wire:target="save">Register School</span>
                <span wire:loading wire:target="save">Registering...</span>
            </button>
        </form>
    @endif
</div>
</div>
