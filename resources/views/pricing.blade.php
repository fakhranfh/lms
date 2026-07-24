@extends('master')

@section('title', 'Pricing')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    <!-- Navbar -->
    <header class="sticky top-0 z-40 border-b border-outline-variant bg-background/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-gutter py-space-md lg:px-space-xl" aria-label="Global">
            <a href="{{ route('home') }}" class="flex items-center gap-space-sm font-headline-sm text-headline-sm text-on-surface">
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

                        @if (Route::has('schools.register'))
                            <a href="{{ route('schools.register') }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-md font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </nav>
    </header>

    <main class="flex-1">
        <section class="mx-auto max-w-6xl px-gutter pb-space-xl pt-space-xl text-center lg:px-space-xl lg:pt-16">
            <h1 class="mx-auto max-w-2xl text-balance font-headline-lg text-headline-lg text-on-surface sm:text-[2.5rem] sm:leading-[1.1] tracking-[-0.03em]">
                Simple pricing that grows with your school
            </h1>
            <p class="mx-auto mt-space-md max-w-lg text-pretty font-body-lg text-body-lg text-on-surface-variant">
                Pick the plan that fits your student count today. Upgrade whenever you need more storage or features.
            </p>
        </section>

        <section class="mx-auto max-w-6xl px-gutter pb-space-xl lg:px-space-xl">
            @if ($tiers->isEmpty())
                <p class="text-center font-body-md text-body-md text-on-surface-variant">
                    Pricing plans are being set up. Please check back soon.
                </p>
            @else
                <div class="grid gap-space-lg sm:grid-cols-2 lg:grid-cols-{{ min($tiers->count(), 4) }}">
                    @foreach ($tiers as $index => $tier)
                        @php
                            $isFeatured = $index === min(1, $tiers->count() - 1);
                        @endphp
                        <div class="reveal relative flex flex-col rounded-xl border {{ $isFeatured ? 'border-primary shadow-[0_8px_30px_rgb(0,0,0,0.06)]' : 'border-outline-variant' }} bg-surface p-space-lg" style="animation-delay: {{ $index * 0.08 }}s">
                            @if ($isFeatured)
                                <span class="absolute -top-3 left-space-lg rounded-full bg-primary px-space-sm py-space-xxs font-label-sm text-label-sm text-on-primary">
                                    Most popular
                                </span>
                            @endif

                            <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $tier->name }}</h2>
                            @if ($tier->description)
                                <p class="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">{{ $tier->description }}</p>
                            @endif

                            <div class="mt-space-lg">
                                @if ((float) $tier->price === 0.0)
                                    <span class="font-headline-lg text-headline-lg text-on-surface">Free</span>
                                @else
                                    <span class="font-headline-lg text-headline-lg text-on-surface">Rp {{ number_format($tier->price, 0, '.', '.') }}</span>
                                    <span class="font-label-md text-label-md text-on-surface-variant">/ {{ strtolower($tier->billing_period->label()) }}</span>
                                @endif
                            </div>

                            <a href="{{ route('schools.register') }}" class="mt-space-lg flex min-h-[44px] items-center justify-center rounded font-label-md text-label-md transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary {{ $isFeatured ? 'bg-primary text-on-primary hover:bg-primary-container' : 'border border-outline-variant text-on-surface hover:border-primary hover:text-primary' }}">
                                Get started
                            </a>

                            @if ($tier->limits->isNotEmpty())
                                <ul class="mt-space-lg space-y-space-sm border-t border-outline-variant pt-space-lg">
                                    @foreach ($tier->limits as $limit)
                                        @php
                                            $limitText = match ($limit->limit_key) {
                                                \App\Enums\TierLimit::MaterialStorageGb->value => $limit->limit_value !== null
                                                    ? number_format($limit->limit_value).' GB material storage'
                                                    : 'Unlimited material storage',
                                                default => ($limit->limit_value !== null ? number_format($limit->limit_value).' ' : '').(\App\Enums\TierLimit::tryFrom($limit->limit_key)?->label() ?? $limit->limit_key),
                                            };
                                        @endphp
                                        <li class="flex items-start gap-space-xs font-body-sm text-body-sm text-on-surface-variant">
                                            <span class="text-success" aria-hidden="true">&#10003;</span>
                                            <span>{{ $limitText }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="border-t border-outline-variant">
            <div class="mx-auto max-w-6xl px-gutter py-space-xl text-center lg:px-space-xl">
                <h2 class="font-headline-md text-headline-md text-on-surface">Not sure which plan fits?</h2>
                <p class="mx-auto mt-space-xs max-w-md font-body-md text-body-md text-on-surface-variant">Start on any tier and change later as your school grows &mdash; no lock-in.</p>
                <div class="mt-space-lg flex justify-center">
                    <a href="{{ route('schools.register') }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                        Register your school &rarr;
                    </a>
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
