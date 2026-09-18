<div class="min-h-screen flex items-center justify-center bg-surface-container p-gutter"
    x-data="{
        password: @js($password),
        passwordConfirmation: @js($password_confirmation),
        passwordTouched: false,
        passwordConfirmationTouched: false,
        submitAttempted: false,
        get passwordMismatch() { return this.passwordConfirmation.length > 0 && this.password !== this.passwordConfirmation },
        get passwordInvalid() { return this.password === '' || window.PasswordPolicy.invalid(this.password) },
        get passwordConfirmationInvalid() { return this.passwordMismatch || this.passwordConfirmation === '' },
        get formInvalid() { return this.passwordInvalid || this.passwordConfirmationInvalid },
    }">
    <div class="max-w-md w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Set a New Password</h1>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                For security, you must set a new password before continuing.
            </p>
        </div>

        <form @submit.prevent="submitAttempted = true; if (!formInvalid) { $wire.save() }" class="space-y-space-lg">
            <div>
                <label for="password" class="block font-label-md text-label-md text-on-surface mb-space-xs">New Password</label>
                <input type="password" wire:model="password" id="password" autocomplete="new-password"
                    x-on:input="password = $event.target.value" x-on:blur="passwordTouched = true"
                    class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                    :class="(passwordTouched || submitAttempted) && passwordInvalid ? 'border-error' : 'border-outline-variant'">
                <p x-show="(passwordTouched || submitAttempted) && passwordInvalid" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error" x-text="window.PasswordPolicy.message">
                </p>
                @error('password')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block font-label-md text-label-md text-on-surface mb-space-xs">Confirm Password</label>
                <input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password"
                    x-on:input="passwordConfirmation = $event.target.value" x-on:blur="passwordConfirmationTouched = true"
                    class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                    :class="(passwordConfirmationTouched || submitAttempted) && passwordConfirmationInvalid ? 'border-error' : 'border-outline-variant'">
                <p x-show="(passwordConfirmationTouched || submitAttempted) && passwordConfirmationInvalid" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error">
                    Passwords must match.
                </p>
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
