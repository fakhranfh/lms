@extends('master')

@section('body_class', 'bg-background text-on-background min-h-screen font-body-md flex flex-col')

@section('content')
    <div class="flex flex-1">
        @component('components.admin-sidebar')@endcomponent

        <main class="flex-1 overflow-auto">
            <div class="p-gutter">
                @yield('admin-content')
            </div>
        </main>
    </div>
@endsection
