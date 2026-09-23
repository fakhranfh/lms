@php
    $stack = ['Laravel', 'Livewire', 'Horizon', 'Pulse', 'Cloudflare R2', 'Tailwind'];
@endphp

<section class="border-t border-[--lp-outline] bg-[--lp-dark]">
    <div class="mx-auto max-w-6xl px-gutter py-space-xl text-center lg:px-space-xl">
        <h2 class="text-balance font-headline-md text-headline-md text-[--lp-on-dark] tracking-[-0.02em]">Built on infrastructure you can trust</h2>
        <p class="mx-auto mt-space-sm max-w-lg text-pretty font-body-md text-body-md text-[--lp-on-dark]/70">Every school runs on the same battle-tested stack &mdash; no separate tier, no bolted-on integration.</p>

        <div class="mx-auto mt-space-xl grid max-w-4xl grid-cols-2 gap-space-md sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($stack as $index => $tech)
                <div class="reveal flex h-16 items-center justify-center rounded-lg border border-white/10 bg-white/5 font-label-md text-label-md text-[--lp-on-dark]" style="animation-delay: {{ $index * 0.05 }}s">
                    {{ $tech }}
                </div>
            @endforeach
        </div>
    </div>
</section>
