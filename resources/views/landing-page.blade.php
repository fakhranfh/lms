@extends('master')

@section('title', 'Welcome')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    <div class="lp lp-topbar">
        @include('partials.topbar')
    </div>

    <main class="lp flex-1 bg-[--lp-bg] text-[--lp-ink]">
        <x-landing.hero />
        <x-landing.features />
        <x-landing.tech-stack />
        <x-landing.stats />
        <x-landing.cta />
    </main>

    @include('partials.footer')

    <style>
        .lp {
            --lp-bg: #F9FAFB;
            --lp-surface: #FFFFFF;
            --lp-ink: #073127;
            --lp-muted: #585F6C;
            --lp-outline: #EBEDE8;
            --lp-primary: #004AC6;
            --lp-primary-hover: #2563EB;
            --lp-on-primary: #FFFFFF;
            --lp-accent: #E2FB6C;
            --lp-dark: #004AC6;
            --lp-on-dark: #FFFFFF;
        }

        .lp-topbar header {
            background-color: var(--lp-bg);
            border-color: var(--lp-outline);
        }

        .lp-topbar a,
        .lp-topbar button {
            color: var(--lp-ink) !important;
        }

        .lp-topbar a:hover,
        .lp-topbar button:hover {
            color: var(--lp-primary) !important;
        }

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
