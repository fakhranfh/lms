@extends('master')

@section('title')
    @yield('title')
@endsection

@section('body_class')
    @yield('body_class')
@endsection

@push('styles')
    @stack('styles')
@endpush

@section('content')
    <header class="fixed top-0 w-full z-50 bg-surface border-b border-outline-variant flex items-center justify-center px-gutter h-14 left-0 right-0">
        <img class="mx-auto" src="{{ asset('logo.png') }}" alt="Logo" style="width: 40px; height: auto;">
    </header>

    <main class="w-full max-w-md bg-surface p-space-xl rounded-xl border border-outline-variant shadow-sm mt-16">
        @yield('auth-content')
    </main>

    <footer class="w-full py-space-xl flex flex-col items-center gap-space-sm px-gutter mt-auto max-w-container-max-width mx-auto">
        <img class="mx-auto mb-space-sm" src="{{ asset('logo.png') }}" alt="Logo" style="width: 40px; height: auto;">
        <div class="font-body-sm text-body-sm text-secondary">© {{ date('Y') }} {{ config('app.name') }}</div>
    </footer>
@endsection

@push('scripts')
    @stack('scripts')
@endpush