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
