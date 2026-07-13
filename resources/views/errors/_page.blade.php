@extends('master')

@section('title', $code . ' ' . $title)

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')
    <main class="flex flex-1 items-center justify-center px-gutter py-space-xl">
        <div class="mx-auto max-w-md text-center">
            <p class="font-headline-lg text-headline-lg text-primary sm:text-[4rem] sm:leading-[1.05] tracking-[-0.03em]">
                {{ $code }}
            </p>
            <h1 class="mt-space-md font-headline-md text-headline-md text-on-surface">
                {{ $title }}
            </h1>
            <p class="mt-space-sm text-pretty font-body-lg text-body-lg text-on-surface-variant">
                {{ $message }}
            </p>
            <div class="mt-space-xl flex flex-wrap items-center justify-center gap-x-space-lg gap-y-space-sm">
                <a href="{{ url('/') }}" class="flex min-h-[44px] items-center rounded bg-primary px-space-lg font-label-md text-label-md text-on-primary hover:bg-primary-container focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-colors">
                    Back to Home
                </a>
                <a href="javascript:history.back()" class="group inline-flex items-center gap-space-xs font-label-md text-label-md text-on-surface hover:text-primary transition-colors">
                    <span class="transition-transform group-hover:-translate-x-0.5" aria-hidden="true">&larr;</span>
                    Go back
                </a>
            </div>
        </div>
    </main>
@endsection
