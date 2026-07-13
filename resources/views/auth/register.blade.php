@extends('auth.master')

@section('title', 'Register')

@section('body_class', 'bg-background text-on-background font-body-md min-h-screen flex flex-col items-center justify-center p-gutter')

@section('auth-content')
<div class="text-center mb-space-xl">
    <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Create Account</h2>
</div>

<form class="space-y-space-md" method="POST" action="{{ route('register') }}" onsubmit="return validateRegisterForm(event)">
    @csrf
    <!-- Name Input -->
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xxs" for="name">Full Name</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none" style="font-size: 20px;">person</span>
            <input class="w-full bg-surface border border-outline-variant rounded-lg py-2 pl-10 pr-3 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors h-11" id="name" name="name" placeholder="John Doe" required="" type="text">
        </div>
        @error('name')
        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
        @enderror
    </div>
    <!-- Email Input -->
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xxs" for="email">Email Address</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none" style="font-size: 20px;">mail</span>
            <input class="w-full bg-surface border border-outline-variant rounded-lg py-2 pl-10 pr-3 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors h-11" id="email" name="email" placeholder="john@example.com" required="" type="email">
        </div>
        @error('email')
        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
        @enderror
    </div>
    <!-- Password Input -->
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xxs" for="password">Password</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none" style="font-size: 20px;">lock</span>
            <input class="w-full bg-surface border border-outline-variant rounded-lg py-2 pl-10 pr-10 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors h-11" id="password" name="password" placeholder="••••••••" required="" type="password">
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary hover:text-on-surface transition-colors focus:outline-none" type="button" onclick="togglePasswordVisibility('password', 'toggle-pwd-icon')">
                <span class="material-symbols-outlined" id="toggle-pwd-icon" style="font-size: 20px;">visibility_off</span>
            </button>
        </div>
        @error('password')
        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
        @enderror
        <p class="text-error text-body-sm font-body-sm mt-space-xs" id="password-js-error"></p>
    </div>
    <!-- Confirm Password Input -->
    <div>
        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xxs" for="confirm-password">Confirm Password</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none" style="font-size: 20px;">lock</span>
            <input class="w-full bg-surface border border-outline-variant rounded-lg py-2 pl-10 pr-10 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors h-11" id="confirm-password" name="password_confirmation" placeholder="••••••••" required="" type="password">
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary hover:text-on-surface transition-colors focus:outline-none" type="button" onclick="togglePasswordVisibility('confirm-password', 'toggle-confirm-icon')">
                <span class="material-symbols-outlined" id="toggle-confirm-icon" style="font-size: 20px;">visibility_off</span>
            </button>
        </div>
        @error('password_confirmation')
        <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
        @enderror
        <p class="text-error text-body-sm font-body-sm mt-space-xs" id="confirm-password-js-error"></p>
    </div>
    <!-- Submit Button -->
    <div class="pt-space-sm">
        <button class="w-full bg-primary text-on-primary font-label-md text-label-md rounded-lg py-3 px-4 hover:bg-on-primary-fixed-variant transition-colors flex items-center justify-center gap-2 active:scale-[0.98] h-11" type="submit" id="register-btn">
            <span id="btn-text">Register</span>
            <span class="material-symbols-outlined" id="btn-icon" style="font-size: 18px;">arrow_forward</span>
            <span class="hidden material-symbols-outlined animate-spin" id="btn-loading" style="font-size: 18px;">progress_activity</span>
        </button>
    </div>
</form>
<div class="mt-space-lg text-center border-t border-outline-variant pt-space-md">
    <p class="font-body-sm text-body-sm text-secondary">
        Already have an account?
        <a class="text-primary font-label-md text-label-md hover:underline ml-1" href="{{ route('login') }}">Login here</a>
    </p>
</div>
@endsection

@push('scripts')
<script>
    function renderErrorList(element, messages) {
        if (messages.length === 0) {
            element.textContent = '';
            return;
        }

        element.innerHTML = '<ul class="list-disc pl-5 space-y-1">' + messages.map(function(message) {
            return '<li>' + message + '</li>';
        }).join('') + '</ul>';
    }

    function validateRegisterForm(event) {
        const button = document.getElementById('register-btn');
        const btnText = document.getElementById('btn-text');
        const btnIcon = document.getElementById('btn-icon');
        const btnLoading = document.getElementById('btn-loading');

        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Loading...';
        btnIcon.classList.add('hidden');
        btnLoading.classList.remove('hidden');

        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const passwordError = document.getElementById('password-js-error');
        const confirmPasswordError = document.getElementById('confirm-password-js-error');

        passwordError.textContent = '';
        confirmPasswordError.textContent = '';

        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
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
            if (passwordErrors.length > 0) {
                passwordInput.focus();
            } else {
                confirmPasswordInput.focus();
            }

            event.preventDefault();

            // restore button to original state
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-not-allowed');
            btnText.textContent = 'Register';
            btnIcon.classList.remove('hidden');
            btnLoading.classList.add('hidden');

            return false;
        }

        return true;
    }

    function togglePasswordVisibility(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(iconId);

        if (field.type === 'password') {
            field.type = 'text';
            icon.textContent = 'visibility';
        } else {
            field.type = 'password';
            icon.textContent = 'visibility_off';
        }
    }
</script>
@endpush