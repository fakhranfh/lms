@extends('master')

@section('title', 'Welcome')

@section('body_class', 'bg-background text-on-background h-screen overflow-hidden flex flex-col font-body-md')

@section('content')

    <!-- Navbar -->
    <header class="shrink-0 border-b border-outline-variant bg-background">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-gutter py-space-sm lg:px-space-xl" aria-label="Global">
            <a href="/" class="flex items-center gap-space-sm font-headline-sm text-headline-sm text-on-surface">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="h-6 w-auto" />
                {{ config('app.name', 'Laravel') }}
            </a>

            @if (Route::has('login'))
                <div class="flex items-center gap-space-lg">
                    @if (Route::has('features'))
                        <a href="{{ route('features') }}" class="hidden font-label-md text-label-md text-secondary hover:text-on-surface transition-colors sm:inline">
                            Features
                        </a>
                    @endif
                    @if (Route::has('pricing'))
                        <a href="{{ route('pricing') }}" class="hidden font-label-md text-label-md text-secondary hover:text-on-surface transition-colors sm:inline">
                            Pricing
                        </a>
                    @endif
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="flex h-9 items-center rounded bg-primary px-space-md font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </nav>
    </header>

    <!-- Hero (fills remaining viewport, no scroll) -->
    <main class="flex min-h-0 flex-1 items-center overflow-hidden">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-space-xl px-gutter py-space-md lg:grid-cols-2 lg:px-space-xl">
            <div class="reveal" style="animation-delay: 0.05s">
                <span class="inline-flex items-center gap-space-xs rounded-full border border-outline-variant bg-surface-container-low px-space-sm py-space-xxs font-label-sm text-label-sm text-on-surface-variant">
                    Multi-tenant Learning Platform
                </span>
                <h1 class="mt-space-sm text-balance font-headline-lg text-headline-lg text-on-surface sm:text-[2.25rem] sm:leading-[1.1] lg:text-[2.75rem] lg:leading-[1.05] tracking-[-0.03em]">
                    Launch your own online school in minutes, not months.
                </h1>
                <p class="mt-space-sm max-w-md text-pretty font-body-md text-body-md text-on-surface-variant">
                    Course builder, roles &amp; permissions, AI-assisted grading, and per-school billing &mdash; all wired up so you can focus on teaching, not infrastructure.
                </p>

                <div class="mt-space-lg flex flex-wrap items-center gap-x-space-lg gap-y-space-sm">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="flex h-11 items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="flex h-11 items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                            Start your school &rarr;
                        </a>
                        <a href="{{ route('login') }}" class="group inline-flex items-center gap-space-xs font-label-md text-label-md text-on-surface hover:text-primary transition-colors">
                            Already have an account?
                            <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
                        </a>
                    @endauth
                </div>

                <dl class="mt-space-lg grid grid-cols-3 gap-space-md border-t border-outline-variant pt-space-md">
                    <div>
                        <dt class="font-headline-sm text-headline-sm text-on-surface">&lt; 5 min</dt>
                        <dd class="font-label-sm text-label-sm text-on-surface-variant">To launch a school</dd>
                    </div>
                    <div>
                        <dt class="font-headline-sm text-headline-sm text-on-surface">AI</dt>
                        <dd class="font-label-sm text-label-sm text-on-surface-variant">Assisted grading</dd>
                    </div>
                    <div>
                        <dt class="font-headline-sm text-headline-sm text-on-surface">4 tiers</dt>
                        <dd class="font-label-sm text-label-sm text-on-surface-variant">Flexible pricing</dd>
                    </div>
                </dl>
            </div>

            <div class="reveal hidden lg:block" style="animation-delay: 0.15s">
                <div class="overflow-hidden rounded-lg border border-outline-variant bg-surface shadow-sm">
                    <div class="flex items-center gap-space-xs border-b border-outline-variant bg-surface-container-lowest px-space-md py-space-sm">
                        <span class="h-2.5 w-2.5 rounded-full bg-outline-variant"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-outline-variant"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-outline-variant"></span>
                        <span class="ml-space-xs font-label-sm text-label-sm text-on-surface-variant">yourschool.{{ config('app.domain', 'lms.test') }}</span>
                    </div>
                    <div class="space-y-space-md px-space-lg py-space-md">
                        <div class="flex items-center justify-between">
                            <p class="font-headline-sm text-headline-sm text-on-surface">Introduction to Algebra</p>
                            <span class="rounded-full bg-success-container px-space-sm py-space-xxs font-label-sm text-label-sm text-on-success-container">Published</span>
                        </div>
                        <div class="space-y-space-sm">
                            <div class="flex items-center justify-between rounded border border-outline-variant px-space-sm py-space-xs">
                                <span class="font-body-sm text-body-sm text-on-surface">Module 1 &middot; Linear Equations</span>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">12 lessons</span>
                            </div>
                            <div class="flex items-center justify-between rounded border border-outline-variant px-space-sm py-space-xs">
                                <span class="font-body-sm text-body-sm text-on-surface">Module 2 &middot; Quadratic Functions</span>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">9 lessons</span>
                            </div>
                            <div class="flex items-center justify-between rounded border border-outline-variant px-space-sm py-space-xs">
                                <span class="font-body-sm text-body-sm text-on-surface">Assignment &middot; Graded by AI</span>
                                <span class="font-label-sm text-label-sm text-success">96% score</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-outline-variant">
        <div class="mx-auto max-w-6xl px-gutter py-space-sm font-body-sm text-body-sm text-on-surface-variant lg:px-space-xl">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </div>
    </footer>

    <style>
        @media (prefers-reduced-motion: no-preference) {
            .reveal {
                opacity: 0;
                transform: translateY(14px);
                animation: reveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            @keyframes reveal {
                to { opacity: 1; transform: translateY(0); }
            }
        }
    </style>

@endsection
