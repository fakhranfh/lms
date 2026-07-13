@extends('master')

@section('title', 'Welcome')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    <!-- Navbar -->
    <header class="sticky top-0 z-40 border-b border-outline-variant bg-background/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-gutter py-space-md lg:px-space-xl" aria-label="Global">
            <a href="/" class="flex items-center gap-space-sm font-headline-sm text-headline-sm text-on-surface">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="h-7 w-auto" />
                {{ config('app.name', 'Laravel') }}
            </a>

            @if (Route::has('login'))
                <div class="flex items-center gap-space-lg">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-md font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </nav>
    </header>

    <main class="flex-1">
        <!-- Hero -->
        <section class="mx-auto max-w-6xl px-gutter pb-space-xl pt-space-xl lg:px-space-xl lg:pt-16">
            <div class="grid items-center gap-space-xl lg:grid-cols-2">
                <div class="reveal" style="animation-delay: 0.05s">
                    <h1 class="text-balance font-headline-lg text-headline-lg text-on-surface sm:text-[2.75rem] sm:leading-[1.1] lg:text-[3.25rem] lg:leading-[1.05] tracking-[-0.03em]">
                        A solid foundation, not another blank Laravel install.
                    </h1>
                    <p class="mt-space-md max-w-md text-pretty font-body-lg text-body-lg text-on-surface-variant">
                        Auth, roles, a repository layer, and a CRUD generator already wired up. Skip the scaffolding and start on the part of the app that's actually yours.
                    </p>
                    <div class="mt-space-xl flex flex-wrap items-center gap-x-space-lg gap-y-space-sm">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                                Get Started
                            </a>
                            <a href="{{ route('login') }}" class="group inline-flex items-center gap-space-xs font-label-md text-label-md text-on-surface hover:text-primary transition-colors">
                                Already have an account?
                                <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="reveal" style="animation-delay: 0.15s">
                    <div class="overflow-hidden rounded-lg border border-outline-variant bg-surface">
                        <div class="flex items-center gap-space-xs border-b border-outline-variant bg-surface-container-lowest px-space-md py-space-sm">
                            <span class="h-2.5 w-2.5 rounded-full bg-outline-variant"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-outline-variant"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-outline-variant"></span>
                            <span class="ml-space-xs font-label-sm text-label-sm text-on-surface-variant">terminal</span>
                        </div>
                        <div class="px-space-lg py-space-lg font-mono text-[13px] leading-7 text-on-surface-variant">
                            <p><span class="text-outline">$</span> <span class="text-on-surface">php artisan make:rsc Product --label="Produk"</span></p>
                            <p>&#10003; Model, migration &amp; factory created</p>
                            <p>&#10003; Repository &amp; service layer wired</p>
                            <p>&#10003; Controller &amp; form requests scaffolded</p>
                            <p>&#10003; Index, create, edit &amp; show views generated</p>
                            <p class="mt-space-sm"><span class="text-success">&#10003;</span> <span class="text-on-surface">Ready at /products</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature strip -->
        <section class="border-t border-outline-variant">
            <div class="mx-auto grid max-w-6xl gap-x-space-xl gap-y-space-lg px-gutter py-space-xl sm:grid-cols-3 lg:px-space-xl">
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Authentication, done</h2>
                    <p class="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">Login, registration, password reset and email verification via Fortify, ready before you write a line of code.</p>
                </div>
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Repository pattern</h2>
                    <p class="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">Controllers stay thin. Every entity gets a consistent repository and service layer out of the box.</p>
                </div>
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">CRUD in one command</h2>
                    <p class="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">Generate a full resource, model to views, with a single Artisan command instead of a day of boilerplate.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-outline-variant">
        <div class="mx-auto max-w-6xl px-gutter py-space-lg font-body-sm text-body-sm text-on-surface-variant lg:px-space-xl">
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
