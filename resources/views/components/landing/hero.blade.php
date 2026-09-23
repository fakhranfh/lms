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
</section>
