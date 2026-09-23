@extends('master')

@section('title', 'Welcome')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')

    <div class="lp lp-topbar">
        @include('partials.topbar')
    </div>

    <main class="lp flex-1 bg-[--lp-bg] text-[--lp-ink]">
        <!-- Hero -->
        <section class="mx-auto max-w-5xl px-gutter pb-space-xl pt-space-xl text-center lg:px-space-xl lg:pt-20">
            <h1 class="reveal mx-auto mt-space-sm max-w-3xl text-balance font-headline-lg text-headline-lg text-[--lp-ink] sm:text-[2.75rem] sm:leading-[1.08] lg:text-[3.25rem] lg:leading-[1.05] tracking-[-0.03em]" style="animation-delay: 0.1s">
                Launch your own online school in <span class="underline decoration-[--lp-accent] decoration-4 underline-offset-4">minutes</span>, not months.
            </h1>
            <p class="reveal mx-auto mt-space-md max-w-xl text-pretty font-body-md text-body-md text-[--lp-muted]" style="animation-delay: 0.15s">
                Courses, exams, attendance, gradebooks, forums, and video classes &mdash; one platform per school, wired up from day one.
            </p>

            <div class="reveal mt-space-lg flex flex-wrap items-center justify-center gap-x-space-lg gap-y-space-sm" style="animation-delay: 0.2s">
                @auth
                    <a href="{{ url('/dashboard') }}" class="flex h-11 items-center rounded bg-[--lp-primary] px-space-lg font-label-md text-label-md text-[--lp-on-primary] hover:bg-[--lp-primary-hover] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[--lp-primary] transition-colors">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex h-11 items-center rounded bg-[--lp-primary] px-space-lg font-label-md text-label-md text-[--lp-on-primary] hover:bg-[--lp-primary-hover] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[--lp-primary] transition-colors">
                        Login &rarr;
                    </a>
                    @if (Route::has('try-demo'))
                        <a href="{{ route('try-demo') }}" class="flex h-11 items-center rounded border border-[--lp-outline] bg-[--lp-surface] px-space-lg font-label-md text-label-md text-[--lp-ink] hover:border-[--lp-ink] transition-colors">
                            Try Demo
                        </a>
                    @endif
                @endauth
            </div>

            <dl class="reveal mx-auto mt-space-xl grid max-w-2xl grid-cols-3 gap-space-md border-t border-[--lp-outline] pt-space-lg" style="animation-delay: 0.34s">
                <div>
                    <dt class="font-headline-sm text-headline-sm text-[--lp-ink]">&lt; 5 min</dt>
                    <dd class="font-label-sm text-label-sm text-[--lp-muted]">To launch a school</dd>
                </div>
                <div>
                    <dt class="font-headline-sm text-headline-sm text-[--lp-ink]">Live</dt>
                    <dd class="font-label-sm text-label-sm text-[--lp-muted]">Video classes &amp; exam proctoring</dd>
                </div>
                <div>
                    <dt class="font-headline-sm text-headline-sm text-[--lp-ink]">8+</dt>
                    <dd class="font-label-sm text-label-sm text-[--lp-muted]">Tools for teachers to build on</dd>
                </div>
            </dl>
        </section>

        <!-- Feature highlights -->
        <section class="border-t border-[--lp-outline] bg-[--lp-surface]">
            <div class="mx-auto max-w-6xl px-gutter py-space-xl lg:px-space-xl">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-balance font-headline-md text-headline-md text-[--lp-ink] tracking-[-0.02em]">Everything a school runs on, in one place</h2>
                    <p class="mx-auto mt-space-sm max-w-lg text-pretty font-body-md text-body-md text-[--lp-muted]">From syllabus to report card, {{ config('app.name', 'Laravel') }} covers the academic year end to end &mdash; for admins, instructors, and students alike.</p>
                </div>

                <div class="mt-space-xl grid gap-space-lg lg:grid-cols-6">
                    <div class="reveal flex flex-col justify-between gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-4" style="animation-delay: 0.02s">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Syllabus &amp; course builder</h3>
                            <p class="mt-space-xs max-w-md font-body-sm text-body-sm text-[--lp-muted]">Structure courses into modules and sessions, define learning outcomes and rubrics, and set class policies &mdash; then publish when ready.</p>
                        </div>
                        <div class="overflow-hidden rounded-lg border border-[--lp-outline] bg-[--lp-surface]">
                            <div class="space-y-space-xs px-space-md py-space-md">
                                <div class="flex items-center justify-between rounded bg-[--lp-bg] px-space-sm py-space-xs">
                                    <span class="font-body-sm text-body-sm text-[--lp-ink]">Session 1 &middot; Linear Equations</span>
                                    <span class="font-label-sm text-label-sm text-[--lp-muted]">Learning outcome set</span>
                                </div>
                                <div class="flex items-center justify-between rounded bg-[--lp-bg] px-space-sm py-space-xs">
                                    <span class="font-body-sm text-body-sm text-[--lp-ink]">Session 2 &middot; Quadratic Functions</span>
                                    <span class="font-label-sm text-label-sm text-[--lp-muted]">Rubric attached</span>
                                </div>
                                <div class="flex items-center justify-between rounded bg-[--lp-bg] px-space-sm py-space-xs">
                                    <span class="font-body-sm text-body-sm text-[--lp-ink]">Class policy</span>
                                    <span class="font-label-sm text-label-sm text-[--lp-muted]">Published</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.06s">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Assessments &amp; grading</h3>
                            <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Quizzes, assignments, and final exams with a queue-monitored scoring pipeline for instructors to review and finalize.</p>
                        </div>
                        <div class="flex items-center justify-between rounded border border-[--lp-outline] px-space-sm py-space-xs">
                            <span class="font-body-sm text-body-sm text-[--lp-ink]">Essay &middot; Photosynthesis</span>
                            <span class="font-label-sm text-label-sm text-[--lp-primary]">Scored</span>
                        </div>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.1s">
                        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Exam proctoring</h3>
                        <p class="font-body-sm text-body-sm text-[--lp-muted]">Live sessions capture snapshots and integrity events during timed exams, so instructors can review anything flagged after the fact.</p>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.14s">
                        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">QR &amp; geo attendance</h3>
                        <p class="font-body-sm text-body-sm text-[--lp-muted]">Students check in per session by QR code, with location verification and automatic scoring against the attendance policy.</p>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.18s">
                        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Gradebook &amp; report cards</h3>
                        <p class="font-body-sm text-body-sm text-[--lp-muted]">Scores from assessments, attendance, and participation roll up automatically into a per-student gradebook and report card.</p>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-3" style="animation-delay: 0.22s">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Video conferencing</h3>
                            <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Hold live classes with built-in video sessions and participation tracked per student &mdash; no third-party meeting link to juggle.</p>
                        </div>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-3" style="animation-delay: 0.26s">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Discussion forums</h3>
                            <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Threaded course discussions with read tracking and participation scoring, so forum activity can count toward a grade.</p>
                        </div>
                    </div>

                    <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.3s">
                        <div class="flex flex-wrap items-center justify-between gap-space-sm">
                            <div>
                                <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Media library &amp; cloud storage</h3>
                                <p class="mt-space-xs max-w-lg font-body-sm text-body-sm text-[--lp-muted]">Upload and version lesson materials &mdash; video, PDF, audio, images &mdash; to cloud storage, with usage monitored per school.</p>
                            </div>
                            <div class="flex items-center gap-space-md">
                                <div class="h-2 w-32 overflow-hidden rounded-full bg-[--lp-outline]">
                                    <div class="h-full w-3/5 rounded-full bg-[--lp-primary]"></div>
                                </div>
                                <span class="shrink-0 font-label-sm text-label-sm text-[--lp-muted]">Storage in use</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tech foundation -->
        <section class="border-t border-[--lp-outline] bg-[--lp-dark]">
            <div class="mx-auto max-w-6xl px-gutter py-space-xl text-center lg:px-space-xl">
                <h2 class="text-balance font-headline-md text-headline-md text-[--lp-on-dark] tracking-[-0.02em]">Built on infrastructure you can trust</h2>
                <p class="mx-auto mt-space-sm max-w-lg text-pretty font-body-md text-body-md text-[--lp-on-dark]/70">Every school runs on the same battle-tested stack &mdash; no separate tier, no bolted-on integration.</p>

                <div class="mx-auto mt-space-xl grid max-w-4xl grid-cols-2 gap-space-md sm:grid-cols-3 lg:grid-cols-6">
                    @foreach (['Laravel', 'Livewire', 'Horizon', 'Pulse', 'Cloudflare R2', 'Tailwind'] as $index => $tech)
                        <div class="reveal flex h-16 items-center justify-center rounded-lg border border-white/10 bg-white/5 font-label-md text-label-md text-[--lp-on-dark]" style="animation-delay: {{ $index * 0.05 }}s">
                            {{ $tech }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="border-t border-[--lp-outline] bg-[--lp-surface]">
            <div class="mx-auto max-w-6xl px-gutter py-space-xl lg:px-space-xl">
                <div class="grid grid-cols-1 gap-space-lg divide-y divide-[--lp-outline] sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="pt-space-md text-center sm:pt-0">
                        <p class="font-headline-lg text-headline-lg text-[--lp-ink]">&lt; 5 min</p>
                        <p class="mt-space-xs font-label-sm text-label-sm text-[--lp-muted]">From sign-up to a live school</p>
                    </div>
                    <div class="pt-space-md text-center sm:pt-0">
                        <p class="font-headline-lg text-headline-lg text-[--lp-ink]">Role-based</p>
                        <p class="mt-space-xs font-label-sm text-label-sm text-[--lp-muted]">School-scoped access for admins, instructors &amp; students</p>
                    </div>
                    <div class="pt-space-md text-center sm:pt-0">
                        <p class="font-headline-lg text-headline-lg text-[--lp-ink]">Isolated</p>
                        <p class="mt-space-xs font-label-sm text-label-sm text-[--lp-muted]">Data per school, no cross-tenant leakage</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="border-t border-[--lp-outline] bg-[--lp-dark]">
            <div class="mx-auto flex max-w-6xl flex-col items-center gap-space-md px-gutter py-space-xl text-center lg:px-space-xl">
                <h2 class="text-balance font-headline-md text-headline-md text-[--lp-on-dark] tracking-[-0.02em]">Ready to launch your school?</h2>
                <p class="max-w-md text-pretty font-body-md text-body-md text-[--lp-on-dark]/70">Try a fully seeded demo, or log in if you already have a school.</p>
                <div class="mt-space-sm flex flex-wrap justify-center gap-space-md">
                    <a href="{{ route('login') }}" class="flex h-11 items-center rounded bg-[--lp-accent] px-space-lg font-label-md text-label-md text-[--lp-dark] hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[--lp-accent] transition-opacity">
                        Login &rarr;
                    </a>
                    @if (Route::has('try-demo'))
                        <a href="{{ route('try-demo') }}" class="flex h-11 items-center rounded border border-white/20 px-space-lg font-label-md text-label-md text-[--lp-on-dark] hover:bg-white/5 transition-colors">
                            Try Demo
                        </a>
                    @endif
                </div>
            </div>
        </section>
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
