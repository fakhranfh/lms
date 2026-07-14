@extends('master')

@section('title', 'Register your school')

@section('body_class', 'bg-background text-on-background min-h-screen flex items-center justify-center p-gutter font-body-md')

@section('content')
    <div class="w-full max-w-[420px] bg-surface rounded-xl p-space-xl border border-outline-variant shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div class="text-center mb-space-xl">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-space-xxs">Register your school</h2>
        </div>

        @if (session('status'))
            <div class="mb-space-md rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700 text-body-sm font-body-sm">
                {{ session('status') }}
            </div>
        @endif

        <form class="space-y-space-md" method="POST" action="{{ route('schools.store') }}">
            @csrf

            <div class="space-y-space-xs">
                <label class="block font-label-md text-label-md text-on-surface" for="name">School Name</label>
                <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('name') border-error @enderror" id="name" name="name" placeholder="My School" required value="{{ old('name') }}">
                @error('name')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-space-xs">
                <label class="block font-label-md text-label-md text-on-surface" for="domain">Subdomain</label>
                <input class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none @error('domain') border-error @enderror" id="domain" name="domain" placeholder="myschool.{{ config('app.domain') }}" required value="{{ old('domain') }}">
                @error('domain')
                    <p class="text-error text-body-sm font-body-sm mt-space-xs">{{ $message }}</p>
                @enderror
            </div>

            <button class="w-full h-[44px] mt-space-lg bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all" type="submit">
                Register School
            </button>
        </form>
    </div>
@endsection
