@extends('auth.master')

@section('title', 'Forgot Password')

@section('body_class', 'bg-background text-on-background font-body-md min-h-screen flex flex-col items-center justify-center p-gutter')

@section('auth-content')
<div class="text-center mb-space-xl">
    <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Forgot Password</h2>
    <p class="mt-2 text-center font-body-sm text-body-sm text-secondary">
        Enter your email to receive password reset instructions.
    </p>
</div>

@include('auth.success-and-error-alert')

<form class="flex flex-col gap-space-md mt-space-sm" method="POST" id="forgot-password-form" onsubmit="return validateForm(event)">
    @csrf
    <div class="flex flex-col gap-space-xxs">
        <label class="font-label-md text-label-md text-on-surface" for="email">Email Address</label>
        <div class="relative">
            <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span
                    class="material-symbols-outlined text-[20px] text-outline">mail</span>
            </div>
            <input class="w-full pl-10 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface placeholder-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                id="email" name="email" placeholder="Enter your email" required=""
                type="email" />
        </div>
    </div>
    <button class="w-full bg-primary text-on-primary font-label-md text-label-md py-3.5 rounded-lg hover:bg-surface-tint active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-space-xs mt-space-sm disabled:opacity-60 disabled:cursor-not-allowed"
        id="submit-btn" type="submit">
        <span id="btn-text">Send Instructions</span>
        <span id="btn-icon" class="material-symbols-outlined text-[18px]">send</span>
    </button>
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
<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>
<script>
    // Close button handler
    document.getElementById('close-error-alert').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('error-alert').classList.add('hidden');
    });

    function validateForm(event) {
        const emailInput = document.getElementById('email');
        const errorAlert = document.getElementById('error-alert');
        const errorList = document.getElementById('error-list');
        const submitBtn = document.getElementById('submit-btn');
        const errors = [];

        errorList.innerHTML = '';
        errorAlert.classList.add('hidden');

        const email = emailInput ? emailInput.value.trim() : '';

        if (!email) {
            errors.push('Email address is required.');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.push('Please enter a valid email address.');
        } else if (email.length > 254) {
            errors.push('Email address is too long.');
        }

        if (errors.length > 0) {
            errors.forEach(function(err) {
                const li = document.createElement('li');
                li.textContent = err;
                errorList.appendChild(li);
            });
            errorAlert.classList.remove('hidden');
            if (emailInput) emailInput.focus();
            event.preventDefault();
            return false;
        }

        // Show loading state
        submitBtn.disabled = true;
        document.getElementById('btn-text').textContent = 'Sending...';
        document.getElementById('btn-icon').textContent = 'autorenew';
        document.getElementById('btn-icon').style.animation = 'spin 1s linear infinite';

        return true;
    }
</script>
@endpush