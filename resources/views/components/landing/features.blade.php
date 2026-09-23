<section class="border-t border-[--lp-outline] bg-[--lp-surface]">
    <div class="mx-auto max-w-6xl px-gutter py-space-xl lg:px-space-xl">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-balance font-headline-md text-headline-md text-[--lp-ink] tracking-[-0.02em]">Everything a school runs on, in one place</h2>
            <p class="mx-auto mt-space-sm max-w-lg text-pretty font-body-md text-body-md text-[--lp-muted]">From course builder to report cards, {{ config('app.name', 'Laravel') }} covers the academic year end to end &mdash; for admins, teacher, and students alike.</p>
        </div>

        <div class="mt-space-xl grid grid-cols-1 gap-space-lg">
            <x-landing.course-builder-card />
            <x-landing.forums-card />
            <x-landing.assessments-card />
            <x-landing.proctoring-card />
            <x-landing.gradebook-card />
        </div>
    </div>
</section>
