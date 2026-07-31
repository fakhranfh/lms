@extends('master')

@section('title', 'Features')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    @include('partials.topbar')

    <main class="flex-1">
        <section class="mx-auto max-w-6xl px-gutter pb-space-xl pt-space-xl text-center lg:px-space-xl lg:pt-16">
            <h1 class="mx-auto max-w-2xl text-balance font-headline-lg text-headline-lg text-on-surface sm:text-[2.5rem] sm:leading-[1.1] tracking-[-0.03em]">
                Everything you need to run an online school
            </h1>
            <p class="mx-auto mt-space-md max-w-lg text-pretty font-body-md text-body-md text-on-surface-variant">
                From course building to grading to billing, {{ config('app.name', 'Laravel') }} handles the infrastructure so you can focus on teaching.
            </p>
        </section>

        <section class="mx-auto max-w-6xl px-gutter pb-space-xl lg:px-space-xl">
            <div class="grid gap-space-lg sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        [
                            'icon' => '&#128218;',
                            'title' => 'Course Builder',
                            'description' => 'Structure courses into modules and lessons with a drag-and-drop builder, then attach video, PDF, audio, image, and interactive materials to any lesson.',
                        ],
                        [
                            'icon' => '&#128274;',
                            'title' => 'Roles & Permissions',
                            'description' => 'School-scoped role-based access control lets you define admins, teachers, and students with fine-grained permissions per school.',
                        ],
                        [
                            'icon' => '&#129302;',
                            'title' => 'AI-Assisted Grading',
                            'description' => 'Automatically grade student submissions with AI, complete with prompt-injection safeguards, queue monitoring, and event logging for full auditability.',
                        ],
                        [
                            'icon' => '&#128179;',
                            'title' => 'Per-School Billing',
                            'description' => 'Flexible pricing tiers with Midtrans and Xendit payment gateway integration, feature gating, and automatic tier assignment per school.',
                        ],
                        [
                            'icon' => '&#128190;',
                            'title' => 'Cloud Material Storage',
                            'description' => 'Upload and version lesson materials to R2 storage with per-tier quotas, from 1GB on the Basic plan up to 100GB on Max.',
                        ],
                        [
                            'icon' => '&#128272;',
                            'title' => 'Compliance & Observability',
                            'description' => 'Built-in audit logging plus Horizon and Pulse dashboards give you visibility into queues, performance, and sensitive actions.',
                        ],
                        [
                            'icon' => '&#127760;',
                            'title' => 'Multi-Tenant Domains',
                            'description' => 'Every school gets its own subdomain with isolated data, branding, and demo access &mdash; no cross-tenant leakage.',
                        ],
                        [
                            'icon' => '&#9989;',
                            'title' => 'Progress Tracking',
                            'description' => 'Track lesson completion and student progress automatically as learners move through course materials.',
                        ],
                        [
                            'icon' => '&#9889;',
                            'title' => 'Fast Setup',
                            'description' => 'Register a school and go live in under five minutes with seeded demo content to explore before inviting real students.',
                        ],
                    ];
                @endphp

                @foreach ($features as $index => $feature)
                    <div class="reveal flex flex-col rounded-xl border border-outline-variant bg-surface p-space-lg" style="animation-delay: {{ $index * 0.06 }}s">
                        <span class="text-2xl" aria-hidden="true">{!! $feature['icon'] !!}</span>
                        <h2 class="mt-space-sm font-headline-sm text-headline-sm text-on-surface">{{ $feature['title'] }}</h2>
                        <p class="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">{{ $feature['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border-t border-outline-variant">
            <div class="mx-auto max-w-6xl px-gutter py-space-xl text-center lg:px-space-xl">
                <h2 class="font-headline-md text-headline-md text-on-surface">Ready to launch your school?</h2>
                <p class="mx-auto mt-space-xs max-w-md font-body-md text-body-md text-on-surface-variant">Start on any plan and change later as your school grows &mdash; no lock-in.</p>
                <div class="mt-space-lg flex flex-wrap justify-center gap-space-md">
                    <a href="{{ route('get-started'.\App\Support\RootDomains::currentSuffix()) }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                        Register your school &rarr;
                    </a>
                    @if (Route::has('pricing'.\App\Support\RootDomains::currentSuffix()))
                        <a href="{{ route('pricing'.\App\Support\RootDomains::currentSuffix()) }}" class="flex min-h-[44px] items-center rounded border border-outline-variant px-space-lg font-label-md text-label-md text-on-surface hover:border-primary hover:text-primary transition-colors">
                            View pricing
                        </a>
                    @endif
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
