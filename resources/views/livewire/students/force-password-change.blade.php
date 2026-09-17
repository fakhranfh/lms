<div class="min-h-screen flex items-center justify-center bg-surface-container p-gutter">
    <div class="max-w-md w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Set a New Password</h1>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                For security, you must set a new password before continuing.
            </p>
        </div>

        <form wire:submit="save" class="space-y-space-lg">
            <div>
                <label for="password" class="block font-label-md text-label-md text-on-surface mb-space-xs">New Password</label>
                <input type="password" wire:model="password" id="password" autocomplete="new-password"
                    class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
                @error('password')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block font-label-md text-label-md text-on-surface mb-space-xs">Confirm Password</label>
                <input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password"
                    class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
                @error('password_confirmation')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="w-full px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center justify-center gap-space-sm">
                <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">Save Password</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </form>
    </div>
</div>
