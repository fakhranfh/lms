@section('title', 'Create your admin account')

<div class="flex min-h-screen flex-col">
    @include('partials.topbar')

    <main class="flex flex-1 items-center justify-center p-gutter">
        <div class="w-full max-w-[480px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <div class="text-center mb-space-xl">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Create your admin account</h2>
            </div>

            <form wire:submit="save" class="space-y-space-md">
                <div class="space-y-space-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="name">Your Name</label>
                    <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('name') border-error @enderror" id="name" wire:model="name" placeholder="Jane Doe" required>
                    @error('name')
                        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-space-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="email">Email</label>
                    <input type="email" class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('email') border-error @enderror" id="email" wire:model="email" placeholder="jane@example.com" required>
                    @error('email')
                        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-space-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="password">Password</label>
                    <input type="password" class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('password') border-error @enderror" id="password" wire:model="password" required>
                    @error('password')
                        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-space-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="password_confirmation">Confirm Password</label>
                    <input type="password" class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none" id="password_confirmation" wire:model="password_confirmation" required>
                </div>

                <button wire:loading.attr="disabled" wire:target="save" class="w-full h-[44px] mt-space-lg bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all disabled:opacity-60 disabled:cursor-not-allowed" type="submit">
                    <span wire:loading.remove wire:target="save">Continue</span>
                    <span wire:loading wire:target="save">Creating account...</span>
                </button>
            </form>
        </div>
    </main>

    @include('partials.footer')
</div>
