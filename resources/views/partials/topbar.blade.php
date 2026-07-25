<!-- Navbar -->
<header class="shrink-0 border-b border-outline-variant bg-background">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-gutter py-space-sm lg:px-space-xl" aria-label="Global">
        <a href="{{ route('home') }}" class="flex items-center gap-space-sm font-headline-sm text-headline-sm text-on-surface">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="h-6 w-auto" />
            {{ config('app.name', 'Laravel') }}
        </a>

        <div class="flex items-center gap-space-lg">
            @if (Route::has('features'))
                <a href="{{ route('features') }}" class="hidden font-label-md text-label-md text-secondary hover:text-on-surface transition-colors sm:inline">
                    Features
                </a>
            @endif
            @if (Route::has('pricing'))
                <a href="{{ route('pricing') }}" class="hidden font-label-md text-label-md text-secondary hover:text-on-surface transition-colors sm:inline">
                    Pricing
                </a>
            @endif
            @auth
                <a href="{{ url('/dashboard') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                    Dashboard
                </a>
            @else
                @if (Route::has('try-demo'))
                    <a href="{{ route('try-demo') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                        Try Demo
                    </a>
                @endif

                @if (Route::has('get-started'))
                    <a href="{{ route('get-started') }}" class="flex h-9 items-center rounded bg-primary px-space-md font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                        Get Started
                    </a>
                @endif
            @endauth
        </div>
    </nav>
</header>
