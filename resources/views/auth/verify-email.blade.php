@extends('auth.master')

@section('title', 'Verify Your Email')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col items-center justify-center p-gutter font-body-md')

@section('auth-content')
    <div class="flex flex-col items-center text-center gap-space-lg">
        <div class="w-20 h-20 rounded-full bg-surface-container-low text-primary flex items-center justify-center">
            <span class="material-symbols-outlined text-[40px]" style="font-variation-settings: 'FILL' 1;">mark_email_read</span>
        </div>

        <div class="space-y-space-xs">
            <h2 class="font-headline-md text-headline-md text-on-surface">Verify your email address</h2>
            <p class="font-body-sm text-body-sm text-secondary">
                We sent a verification link to your inbox. Click it to activate your account and continue.
            </p>
        </div>

        @if (session('status') === 'verification-link-sent')
            <div class="w-full rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-left text-success text-body-sm font-body-sm">
                A new verification link has been sent to the email address you used during registration.
            </div>
        @endif

        <p class="font-body-sm text-body-sm text-on-surface-variant">
            If you did not receive the email, you can request another one.
        </p>

        <form class="w-full flex flex-col gap-space-sm" method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button class="w-full bg-primary text-on-primary font-label-md text-label-md py-3.5 rounded-lg hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-space-xs" type="submit">
                <span class="material-symbols-outlined text-[18px]">send</span>
                Resend Verification Email
            </button>
        </form>

        <div class="pt-space-sm">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button class="font-label-md text-label-md text-primary hover:underline transition-colors flex items-center justify-center gap-1 mx-auto" type="submit">
                    <span class="material-symbols-outlined text-[16px]">logout</span>
                    Log out
                </button>
            </form>
        </div>
    </div>
@endsection