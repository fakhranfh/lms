@extends('master')

@section('title', 'Login')

@section('body_class', 'bg-background text-on-background min-h-screen flex items-center justify-center p-gutter font-body-md')

@push('styles')
    <style>
        #logo {
            width: 50px;
            height: auto;
        }
    </style>
@endpush

@section('content')
    <div class="w-full max-w-[400px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <!-- Header -->
        <div class="text-center mb-space-xl">
            <a href="{{ url('/') }}">
                <img id="logo" class="mx-auto mb-space-sm" src="{{ asset('logo.png') }}" alt="Logo">
            </a>
        </div>
        
        @if (session('status') || session('success'))
            <div class="mb-space-md rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700 text-body-sm font-body-sm">
                {{ session('status') ?? session('success') }}
            </div>
        @endif
        
        <!-- Form -->
        <form class="space-y-space-md" method="POST" action="{{ route('login') }}"> 
            @csrf

            <!-- Email Input -->
            <div class="space-y-space-xs">
                <label class="block font-label-md text-label-md text-on-surface" for="email">Email Address</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="mail">mail</span>
                    <input class="w-full h-[44px] pl-10 pr-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('email') border-error @enderror" id="email" name="email" placeholder="name@example.com" required="" type="email" value="{{ old('email') }}">
                </div>
                @error('email')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>
            <!-- Password Input -->
            <div class="space-y-space-xs">
                <div class="flex items-center justify-between">
                    <label class="block font-label-md text-label-md text-on-surface" for="password">Password</label>
                    @if (Route::has('password.request'))
                        <a class="font-label-md text-label-md text-primary hover:underline transition-colors" href="{{ route('password.request') }}">Forgot Password?</a>
                    @endif
                </div>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="lock">lock</span>
                    <input class="w-full h-[44px] pl-10 pr-10 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('password') border-error @enderror" id="password" name="password" placeholder="••••••••" required="" type="password">
                    <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors focus:outline-none" onclick="togglePassword()" type="button">
                        <span class="material-symbols-outlined" data-icon="visibility" id="toggleIcon">visibility</span>
                    </button>
                </div>
                @error('password')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>
            <!-- Login Button -->
            <button id="loginButton" class="w-full h-[44px] mt-space-lg bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all flex items-center justify-center gap-2 disabled:bg-primary/70 disabled:text-on-primary/80 disabled:cursor-not-allowed" type="submit">
                <span id="loginText">Login</span>
                <span id="loginSpinner" class="hidden flex items-center justify-center">
                    <svg class="animate-spin text-on-primary w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </span>
                <span id="loginArrow" class="material-symbols-outlined text-[18px]" data-icon="arrow_forward">arrow_forward</span>
            </button>
        </form>
        <!-- Register Link -->
        <div class="mt-space-xl text-center">
            <p class="font-body-sm text-body-sm text-secondary">
                Don't have an account?
                <a class="text-primary font-medium hover:underline transition-colors" href="{{ route('register') }}">Register</a>
            </p>
        </div>
    </div>
    <!-- Footer Component Execution -->
    <div class="fixed bottom-0 w-full py-space-xl border-t border-outline-variant bg-background flex flex-col items-center gap-space-sm px-gutter">
        <p class="font-body-sm text-body-sm text-secondary opacity-80">© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility_off';
                icon.setAttribute('data-icon', 'visibility_off');
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility';
                icon.setAttribute('data-icon', 'visibility');
            }
        }

        // Disable login button on submit and show loading spinner
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const loginButton = document.getElementById('loginButton');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            const loginArrow = document.getElementById('loginArrow');

            if (form && loginButton) {
                form.addEventListener('submit', function (e) {
                    // prevent double submission if already disabled
                    if (loginButton.disabled) {
                        e.preventDefault();
                        return;
                    }

                    loginButton.disabled = true;
                    loginButton.setAttribute('aria-disabled', 'true');
                    // keep the "Login" text visible while showing the spinner
                    loginSpinner.classList.remove('hidden');
                    if (loginArrow) loginArrow.classList.add('hidden');
                });
            }
        });
    </script>
@endpush