<!-- Navbar -->
<header class="shrink-0 border-b border-outline-variant bg-background">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-gutter py-space-sm lg:px-space-xl" aria-label="Global">
        <a href="{{ route('home') }}" class="flex items-center gap-space-sm font-headline-sm text-headline-sm text-on-surface">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="h-6 w-auto" />
            {{ config('app.name', 'Laravel') }}
        </a>

        <div class="flex items-center gap-space-lg">
            @auth
                <a href="{{ url('/dashboard') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                    Dashboard
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                        Logout
                    </button>
                </form>
            @else
                @if (Route::has('try-demo'))
                    <a href="{{ route('try-demo') }}" class="topbar-cta-outline flex h-11 items-center rounded border border-outline-variant bg-surface px-space-lg font-label-md text-label-md text-on-surface hover:border-on-surface transition-colors">
                        Try Demo
                    </a>
                @endif

                <a href="{{ route('login') }}" class="topbar-cta-primary flex h-11 items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container transition-colors">
                    Login &rarr;
                </a>

            @endauth
        </div>
    </nav>
</header>
