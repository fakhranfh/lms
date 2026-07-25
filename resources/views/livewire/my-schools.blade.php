@section('title', 'My Schools')

<div class="flex min-h-screen flex-col">
    @include('partials.topbar')

    <main class="flex flex-1 items-center justify-center p-gutter">
        <div class="w-full max-w-[560px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <div class="flex items-center justify-between mb-space-lg">
                <h2 class="font-headline-md text-headline-md text-on-surface">My Schools</h2>
                <a href="{{ route('get-started.school') }}" class="flex h-9 items-center rounded bg-primary px-space-md font-label-md text-label-md text-on-primary hover:bg-primary-container transition-colors">
                    Add School
                </a>
            </div>

            @if ($schools->isEmpty())
                <p class="font-body-md text-body-md text-secondary text-center py-space-lg">You don't manage any schools yet.</p>
            @else
                <ul class="space-y-space-sm">
                    @foreach ($schools as $school)
                        @php
                            $port = request()->getPort();
                            $schoolUrl = $port && ! in_array($port, [80, 443])
                                ? request()->getScheme().'://'.$school->domain.':'.$port
                                : request()->getScheme().'://'.$school->domain;
                        @endphp
                        <li class="flex items-center justify-between h-[52px] px-space-md rounded-lg border border-outline-variant bg-surface-container-lowest">
                            <span class="font-body-md text-body-md text-on-surface">{{ $school->name }}</span>
                            <a href="{{ $schoolUrl }}" class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors">
                                {{ $school->domain }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </main>

    @include('partials.footer')
</div>
