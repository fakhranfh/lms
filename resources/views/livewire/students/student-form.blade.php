@section('title', $this->isEditing() ? 'Edit Student' : 'New Student')

<div class="w-full" x-data="{
        showErrorModal: false, errorMessage: '',
        savedPhotoSrc: @js($this->savedPhotoSrc()),
        name: @js($name),
        email: @js($email),
        password: @js($password),
        passwordConfirmation: @js($password_confirmation),
        loginLinkTtlDays: @js((string) $loginLinkTtlDays),
        isEditing: @js($this->isEditing()),
        nameTouched: false,
        emailTouched: false,
        loginLinkTtlDaysTouched: false,
        submitAttempted: false,
        get nameEmpty() { return this.name.trim() === '' },
        get emailInvalid() { return this.email.trim() === '' || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email) },
        get loginLinkTtlDaysInvalid() {
            const days = Number(this.loginLinkTtlDays);
            return this.loginLinkTtlDays === '' || ! Number.isInteger(days) || days < 1 || days > 365;
        },
        get passwordTooWeak() { return this.password.length > 0 && !!window.PasswordPolicy?.invalid(this.password) },
        get passwordMismatch() { return this.passwordConfirmation.length > 0 && this.password !== this.passwordConfirmation },
        get passwordInvalid() {
            if (this.password === '' && this.passwordConfirmation === '' && this.isEditing) { return false }
            return this.passwordTooWeak || this.passwordMismatch || this.password === '' || this.passwordConfirmation === ''
        },
        get formInvalid() { return this.nameEmpty || this.emailInvalid || this.loginLinkTtlDaysInvalid || this.passwordInvalid },
        previewPhoto(event) {
            const file = event.target.files[0];
            if (! file) { return }

            const reader = new FileReader();
            reader.onload = (e) => { this.$refs.photoPreview.src = e.target.result };
            reader.readAsDataURL(file);
        },
        cancelPhotoPreview() {
            this.$refs.photoPreview.src = this.savedPhotoSrc;
        },
    }"
    x-init="
        $wire.$on('show-error-modal', ({ message }) => { errorMessage = message; showErrorModal = true });
        $wire.$on('student-autofilled', ({ name: newName, email: newEmail, password: pwd }) => {
            name = newName; email = newEmail; password = pwd; passwordConfirmation = pwd;
        });
    ">

    <div class="mb-space-lg">
        <a href="{{ route('students.index') }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Students
        </a>
        <h1 class="font-headline-sm text-headline-sm text-on-surface mt-space-sm">
            {{ $this->isEditing() ? 'Edit Student - '.$this->user->name : 'New Student' }}
        </h1>
    </div>

    @if ($loginUrl && ! $this->isEditing())
        <div class="mb-space-lg px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg space-y-space-sm" x-data="{ copied: false }">
            <p class="font-label-md text-label-md text-success">Student created. Share this one-time login link:</p>
            <div class="flex items-center gap-space-sm">
                <input type="text" readonly value="{{ $loginUrl }}" x-ref="loginUrlInput"
                    class="flex-1 px-space-md py-space-sm border border-outline-variant rounded-lg font-body-sm text-body-sm bg-surface">
                <button type="button"
                    @click="navigator.clipboard.writeText($refs.loginUrlInput.value); copied = true; setTimeout(() => copied = false, 2000)"
                    class="px-space-md py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity">
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
        </div>
    @endif

    @if ($loginUrl && $this->isEditing())
        <div wire:key="login-url-modal-{{ md5($loginUrl) }}" x-data="{ copied: false, show: true }">
            <x-ui.modal show="show" onClose="show = false" maxWidth="max-w-lg">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg p-space-lg space-y-space-md">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">New Login Link Generated</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Share this one-time login link with the student:</p>
                    <div class="flex items-center gap-space-sm">
                        <input type="text" readonly value="{{ $loginUrl }}" x-ref="regeneratedLoginUrlInput"
                            class="flex-1 px-space-md py-space-sm border border-outline-variant rounded-lg font-body-sm text-body-sm bg-surface">
                        <button type="button"
                            @click="navigator.clipboard.writeText($refs.regeneratedLoginUrlInput.value); copied = true; setTimeout(() => copied = false, 2000)"
                            class="px-space-md py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity">
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                    <div class="flex justify-end pt-space-sm">
                        <button type="button" @click="show = false" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                            Close
                        </button>
                    </div>
                </div>
            </x-ui.modal>
        </div>
    @endif

    <form @submit.prevent="submitAttempted = true; if (!formInvalid) { $wire.save() }" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <!-- Profile Photo -->
        <div class="flex flex-col md:flex-row items-center md:items-start gap-space-lg pb-space-lg border-b border-outline-variant">
            <label for="photo" class="relative group/avatar cursor-pointer flex-shrink-0">
                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-surface-container-low shadow-sm relative hover:shadow-lg transition-shadow">
                    <img x-ref="photoPreview" src="{{ $this->savedPhotoSrc() }}" alt="Photo" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-on-surface/40 flex items-center justify-center opacity-0 group-hover/avatar:opacity-100 transition-opacity duration-200">
                        <span class="material-symbols-outlined text-surface text-[28px]">photo_camera</span>
                    </div>
                </div>
            </label>
            <input type="file" id="photo" wire:model="photo" x-on:change="previewPhoto($event)" accept="image/jpeg,image/png,image/gif" class="hidden" />
            <div class="text-center md:text-left">
                <h3 class="font-label-md text-label-md text-on-surface">Photo</h3>
                <p class="font-body-sm text-body-sm text-secondary mt-space-xs">JPG, GIF or PNG. Max size of 5MB.</p>
                <div class="mt-space-sm flex items-center gap-space-md">
                    <label for="photo" class="font-label-md text-label-md text-primary hover:underline cursor-pointer">Change Photo</label>
                    @if ($photo)
                        <button type="button" x-on:click="cancelPhotoPreview()" wire:click="cancelPhoto" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">Cancel</button>
                    @endif
                </div>
                @error('photo')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($this->canAutofill())
            <div class="flex justify-end">
                <button type="button" wire:click="autofill" wire:loading.attr="disabled" wire:target="autofill"
                    class="px-space-md py-space-xs border border-outline-variant rounded-lg font-label-sm text-label-sm text-secondary hover:text-on-surface hover:bg-surface-container-low transition-colors inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[16px]">auto_fix_high</span>
                    <span wire:loading.remove wire:target="autofill">Autofill</span>
                    <span wire:loading wire:target="autofill">Filling...</span>
                </button>
            </div>
        @endif

        <div>
            <label for="name" class="block font-label-md text-label-md text-on-surface mb-space-xs">Name</label>
            <input type="text" wire:model="name" id="name"
                x-on:input="name = $event.target.value" x-on:blur="nameTouched = true"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                :class="(nameTouched || submitAttempted) && nameEmpty ? 'border-error' : 'border-outline-variant'">
            <p x-show="(nameTouched || submitAttempted) && nameEmpty" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error">
                Name is required.
            </p>
            @error('name')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block font-label-md text-label-md text-on-surface mb-space-xs">Email</label>
            <input type="email" wire:model="email" id="email"
                x-on:input="email = $event.target.value" x-on:blur="emailTouched = true"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                :class="(emailTouched || submitAttempted) && emailInvalid ? 'border-error' : 'border-outline-variant'">
            <p x-show="(emailTouched || submitAttempted) && emailInvalid" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error">
                Enter a valid email address.
            </p>
            @error('email')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block font-label-md text-label-md text-on-surface mb-space-xs">
                Password {{ $this->isEditing() ? '(leave blank to keep current)' : '' }}
            </label>
            <input type="password" wire:model="password" id="password" autocomplete="new-password"
                x-on:input="password = $event.target.value"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                :class="passwordTooWeak ? 'border-error' : 'border-outline-variant'">
            <p x-show="passwordTooWeak" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error" x-text="window.PasswordPolicy?.message ?? 'Password does not meet the requirements.'">
            </p>
            @error('password')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block font-label-md text-label-md text-on-surface mb-space-xs">
                Confirm Password
            </label>
            <input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password"
                x-on:input="passwordConfirmation = $event.target.value"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                :class="passwordMismatch ? 'border-error' : 'border-outline-variant'">
            <p x-show="passwordMismatch" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error">
                Passwords do not match.
            </p>
            @error('password_confirmation')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="loginLinkTtlDays" class="block font-label-md text-label-md text-on-surface mb-space-xs">
                Login link valid for (days)
            </label>
            <input type="number" wire:model="loginLinkTtlDays" id="loginLinkTtlDays" min="1" max="365"
                x-on:input="loginLinkTtlDays = $event.target.value" x-on:blur="loginLinkTtlDaysTouched = true"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md"
                :class="(loginLinkTtlDaysTouched || submitAttempted) && loginLinkTtlDaysInvalid ? 'border-error' : 'border-outline-variant'">
            <p x-show="(loginLinkTtlDaysTouched || submitAttempted) && loginLinkTtlDaysInvalid" x-cloak class="mt-space-xs font-body-sm text-body-sm text-error">
                Enter a whole number of days between 1 and 365.
            </p>
            @if ($this->isEditing())
                @can('students.edit')
                    <div class="mt-space-sm flex items-center gap-space-md">
                        <button type="button" wire:click="regenerateLoginLink" :disabled="loginLinkTtlDaysInvalid" wire:loading.attr="disabled" wire:target="regenerateLoginLink"
                            class="px-space-md py-space-xs border border-outline-variant rounded-lg font-label-sm text-label-sm text-primary hover:bg-surface-container-low transition-colors inline-flex items-center gap-space-xs disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="material-symbols-outlined text-[16px]">refresh</span>
                            <span wire:loading.remove wire:target="regenerateLoginLink">Generate New Login Link</span>
                            <span wire:loading wire:target="regenerateLoginLink">Generating...</span>
                        </button>
                    </div>
                @endcan
                <p class="mt-space-xs font-body-sm text-body-sm text-secondary">
                    Generate a new one-time link if the student needs another way to log in and set their password.
                </p>
            @else
                <p class="mt-space-xs font-body-sm text-body-sm text-secondary">
                    The student will use this one-time link to log in and set their own password.
                </p>
            @endif
            @error('loginLinkTtlDays')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-space-md">
            <button type="submit" :disabled="formInvalid" wire:loading.attr="disabled" wire:target="save,photo" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>

    <!-- Error Modal -->
    <div x-show="showErrorModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showErrorModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            class="fixed inset-0 flex items-center justify-center p-4"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">error</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Unable to Save Student</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant" x-text="errorMessage"></p>
                    </div>

                    <div class="flex pt-space-md">
                        <button
                            @click="showErrorModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
