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
                    <a href="{{ route('try-demo') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                        Try Demo
                    </a>
                @endif

                <a href="{{ route('login') }}" class="font-label-md text-label-md text-secondary hover:text-on-surface transition-colors">
                    Login
                </a>

            @endauth
        </div>
    </nav>
</header>
