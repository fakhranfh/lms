@section('title', 'Change Password')

<div class="flex items-center justify-center py-space-xl px-gutter">
    <div class="w-full max-w-2xl">
        @if ($updated)
            <div class="mb-space-lg px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
                <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
                <p class="font-body-md text-body-md text-success">Your password has been changed successfully. Please remember to use your new password for future logins.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-space-lg px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-start gap-space-md">
                <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
                <ul class="font-body-md text-body-md text-error list-disc pl-space-md">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-surface rounded-xl border border-outline-variant p-space-lg md:p-space-xl shadow-[0_2px_8px_rgba(0,0,0,0.06)] hover:shadow-[0_4px_12px_rgba(0,0,0,0.1)] transition-shadow duration-200 password-card">
            <div class="relative z-10">
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-space-xs">Change Password</h2>
                <p class="font-body-md text-body-md text-secondary mb-space-xl">Update your password to keep your account secure. Use a strong, unique password.</p>

                <!-- Form -->
                <form class="space-y-space-lg" wire:submit="updatePassword">
                    <!-- Current Password -->
                    <div class="space-y-space-xs" x-data="{ show: false }">
                        <label class="font-label-md text-label-md text-on-surface" for="current_password">Current Password</label>
                        <x-ui.text-input
                            icon="lock"
                            id="current_password"
                            wire:model="current_password"
                            x-bind:type="show ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            class="@error('current_password') border-error @enderror"
                        >
                            <x-slot:right>
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-secondary/60 hover:text-secondary transition-colors"
                                    @click="show = !show"
                                    tabindex="-1"
                                >
                                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                                </button>
                            </x-slot:right>
                        </x-ui.text-input>
                        @error('current_password')
                            <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div class="space-y-space-xs" x-data="{ show: false }">
                        <label class="font-label-md text-label-md text-on-surface" for="password">New Password</label>
                        <x-ui.text-input
                            icon="lock_reset"
                            id="password"
                            wire:model.live.debounce.300ms="password"
                            x-bind:type="show ? 'text' : 'password'"
                            autocomplete="new-password"
                            required
                            class="@error('password') border-error @enderror"
                        >
                            <x-slot:right>
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-secondary/60 hover:text-secondary transition-colors"
                                    @click="show = !show"
                                    tabindex="-1"
                                >
                                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                                </button>
                            </x-slot:right>
                        </x-ui.text-input>
                        @error('password')
                            <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                        @enderror
                        <div class="mt-space-sm">
                            @php
                                $strength = $this->passwordStrength;
                                $strengthClass = $password === '' ? '' : ($strength <= 2 ? 'weak' : ($strength <= 4 ? 'medium' : 'strong'));
                            @endphp
                            <div class="password-strength {{ $strengthClass }}" id="passwordStrength"></div>
                            <p class="text-body-sm text-secondary mt-space-xs">Use at least 8 characters with uppercase, lowercase, numbers, and symbols.</p>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-space-xs" x-data="{ show: false }">
                        <label class="font-label-md text-label-md text-on-surface" for="password_confirmation">Confirm Password</label>
                        <x-ui.text-input
                            icon="verified"
                            id="password_confirmation"
                            wire:model="password_confirmation"
                            x-bind:type="show ? 'text' : 'password'"
                            autocomplete="new-password"
                            required
                        >
                            <x-slot:right>
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-secondary/60 hover:text-secondary transition-colors"
                                    @click="show = !show"
                                    tabindex="-1"
                                >
                                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                                </button>
                            </x-slot:right>
                        </x-ui.text-input>
                    </div>

                    <!-- Security Tips -->
                    <div class="bg-primary/5 border border-primary/20 rounded-lg p-space-md">
                        <div class="flex gap-space-md">
                            <span class="material-symbols-outlined text-primary flex-shrink-0 mt-space-xxs">security</span>
                            <div>
                                <p class="font-label-md text-label-md text-on-surface">Password Security Tips</p>
                                <ul class="text-body-sm text-secondary mt-space-xs space-y-space-xs">
                                    <li>• Use a unique password not used on other accounts</li>
                                    <li>• Include uppercase, lowercase, numbers, and symbols</li>
                                    <li>• Avoid using personal information (name, birthdate, etc.)</li>
                                    <li>• Never share your password with anyone</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-space-lg mt-space-lg border-t border-outline-variant flex justify-end gap-space-md">
                        <a href="{{ route('dashboard') }}" class="px-space-lg py-space-sm rounded-lg border border-outline-variant bg-surface text-on-surface font-label-md text-label-md hover:bg-surface-container-low transition-colors inline-block">
                            Cancel
                        </a>
                        <button id="update-password-btn" wire:loading.attr="disabled" wire:target="updatePassword" class="px-space-lg py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-on-primary-fixed-variant transition-colors flex items-center gap-space-sm shadow-sm disabled:opacity-70 disabled:cursor-not-allowed" type="submit">
                            <span wire:loading.remove wire:target="updatePassword" id="submit-icon" class="material-symbols-outlined text-[18px]">check_circle</span>
                            <span wire:loading wire:target="updatePassword" class="material-symbols-outlined text-[18px] animate-spin">hourglass_empty</span>
                            <span id="submit-text" wire:loading.remove wire:target="updatePassword">Update Password</span>
                            <span wire:loading wire:target="updatePassword">Updating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .password-card {
            position: relative;
            overflow: hidden;
        }

        .password-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #004ac6, #2563eb);
            border-radius: 50%;
            opacity: 0.05;
            pointer-events: none;
        }

        .password-strength {
            height: 6px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .password-strength.weak {
            background-color: rgb(239, 68, 68);
            width: 33%;
        }

        .password-strength.medium {
            background-color: rgb(245, 158, 11);
            width: 66%;
        }

        .password-strength.strong {
            background-color: rgb(34, 197, 94);
            width: 100%;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        #update-password-btn:disabled {
            background-color: #a1a7b8;
            color: #ffffff;
            cursor: not-allowed;
            opacity: 0.7;
        }

        #update-password-btn:disabled:hover {
            background-color: #a1a7b8;
        }
    </style>
@endpush
