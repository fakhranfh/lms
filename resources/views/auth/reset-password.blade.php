@extends('auth.master')

@section('title', 'Reset Password')

@section('body_class', 'bg-background text-on-background font-body-md min-h-screen flex flex-col items-center justify-center p-gutter')

@section('auth-content')
<div class="text-center mb-space-xl">
    <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Reset your password</h2>
    <p class="mt-2 text-center font-body-sm text-body-sm text-secondary">
        Please enter your new password below.
    </p>
</div>
@include('auth.success-and-error-alert')
<form class="space-y-6" id="reset-form" method="POST" action="{{ route('password.update') }}" onsubmit="return validateRegisterForm(event)">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <input type="hidden" name="email" value="{{ $request->email }}">
    <!-- New Password Input -->
    <div>
        <label class="block font-label-md text-label-md text-on-surface" for="new-password">New Password</label>
        <div class="mt-2 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-outline" data-icon="lock">lock</span>
            </div>
            <input
                class="block w-full pl-10 pr-10 sm:text-sm border-outline-variant rounded-lg focus:ring-primary focus:border-primary text-on-surface bg-surface h-[44px]"
                id="new-password" name="password" placeholder="••••••••" required="" type="password">
            <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-outline hover:text-on-surface"
                onclick="togglePasswordVisibility('new-password', this)">
                <span class="material-symbols-outlined" data-icon="visibility">visibility</span>
            </button>
        </div>
        <p id="password-js-error" class="mt-2 font-body-sm text-body-sm text-danger"></p>
    </div>
    <!-- Confirm Password Input -->
    <div>
        <label class="block font-label-md text-label-md text-on-surface" for="confirm-password">Confirm New Password</label>
        <div class="mt-2 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-outline" data-icon="lock_reset">lock_reset</span>
            </div>
            <input
                class="block w-full pl-10 pr-10 sm:text-sm border-outline-variant rounded-lg focus:ring-primary focus:border-primary text-on-surface bg-surface h-[44px]"
                id="confirm-password" name="password_confirmation" placeholder="••••••••" required="" type="password">
            <button
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-outline hover:text-on-surface"
                onclick="togglePasswordVisibility('confirm-password', this)">
                <span class="material-symbols-outlined" data-icon="visibility">visibility</span>
            </button>
        </div>
        <p id="confirm-password-js-error" class="mt-2 font-body-sm text-body-sm text-danger"></p>
    </div>
    <!-- Submit Button -->
    <div>
        <button
            id="reset-submit-button"
            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm font-label-md text-label-md text-on-primary bg-primary hover:bg-on-primary-fixed-variant focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors h-[44px] items-center"
            type="submit">
            <span class="submit-label">Reset Password</span>
            <span class="loading-label hidden items-center gap-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Loading...
            </span>
        </button>
    </div>
</form>
<div class="mt-6 text-center">
    <a href="{{ route('login') }}"
        class="font-label-md text-label-md text-primary hover:text-on-primary-fixed-variant transition-colors flex justify-center items-center gap-1">
        <span class="material-symbols-outlined text-[16px]" data-icon="arrow_back">arrow_back</span>
        Back to log in
    </a>
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        if (!input || !button) return;

        const icon = button.querySelector('.material-symbols-outlined');
        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';

        if (icon) {
            const nextIcon = isPassword ? 'visibility_off' : 'visibility';
            icon.textContent = nextIcon;
            icon.setAttribute('data-icon', nextIcon);
        }
    }

    function validateRegisterForm(event) {
        const passwordInput = document.getElementById('new-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const passwordError = document.getElementById('password-js-error');
        const confirmPasswordError = document.getElementById('confirm-password-js-error');
        const submitButton = document.getElementById('reset-submit-button');

        if (passwordError) passwordError.textContent = '';
        if (confirmPasswordError) confirmPasswordError.textContent = '';

        const password = passwordInput ? passwordInput.value : '';
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
        const passwordErrors = [];
        const confirmPasswordErrors = [];

        if (password.length < 8) {
            passwordErrors.push('Password must be at least 8 characters long.');
        }

        if (!/[a-z]/.test(password) || !/[A-Z]/.test(password)) {
            passwordErrors.push('Password must have both lowercase and uppercase letters.');
        }

        if (!/[0-9]/.test(password)) {
            passwordErrors.push('Password must have at least one number.');
        }

        if (!/[^a-zA-Z0-9]/.test(password)) {
            passwordErrors.push('Password must have at least one symbol.');
        }

        if (password !== confirmPassword) {
            confirmPasswordErrors.push('Password must be the same as the confirmation.');
        }

        renderErrorList(passwordError, passwordErrors);
        renderErrorList(confirmPasswordError, confirmPasswordErrors);

        if (passwordErrors.length > 0 || confirmPasswordErrors.length > 0) {
            if (passwordErrors.length > 0 && passwordInput) {
                passwordInput.focus();
            } else if (confirmPasswordInput) {
                confirmPasswordInput.focus();
            }

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
                const submitLabel = submitButton.querySelector('.submit-label');
                const loadingLabel = submitButton.querySelector('.loading-label');
                if (submitLabel) submitLabel.classList.remove('hidden');
                if (loadingLabel) {
                    loadingLabel.classList.add('hidden');
                    loadingLabel.classList.remove('inline-flex');
                }
            }

            event.preventDefault();
            return false;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-70', 'cursor-not-allowed');
            const submitLabel = submitButton.querySelector('.submit-label');
            const loadingLabel = submitButton.querySelector('.loading-label');
            if (submitLabel) submitLabel.classList.add('hidden');
            if (loadingLabel) {
                loadingLabel.classList.remove('hidden');
                loadingLabel.classList.add('inline-flex');
            }
        }

        return true;
    }

    function renderErrorList(container, errors) {
        if (!container) return;
        if (!errors || errors.length === 0) {
            container.innerHTML = '';
            return;
        }
        const ul = document.createElement('ul');
        ul.className = 'mt-2 list-disc pl-5 text-sm text-danger';
        errors.forEach(function(err) {
            const li = document.createElement('li');
            li.textContent = err;
            ul.appendChild(li);
        });
        container.innerHTML = '';
        container.appendChild(ul);
    }
</script>
@endpush