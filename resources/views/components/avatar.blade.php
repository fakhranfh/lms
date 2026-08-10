@props(['user', 'size' => 8])

@php
    $sizeClass = match ((int) $size) {
        6 => 'w-6 h-6',
        8 => 'w-8 h-8',
        10 => 'w-10 h-10',
        12 => 'w-12 h-12',
        default => 'w-8 h-8',
    };
    $textClass = match (true) {
        $size >= 10 => 'font-headline-sm text-headline-sm',
        $size >= 8 => 'font-label-md text-label-md',
        default => 'font-label-sm text-label-sm',
    };
@endphp

@if ($user?->profile_photo_path)
    <img src="{{ $user->profile_photo_path }}" alt="{{ $user->name }}" class="{{ $sizeClass }} rounded-full object-cover border border-outline-variant flex-shrink-0">
@else
    <div class="{{ $sizeClass }} rounded-full bg-primary flex items-center justify-center text-on-primary {{ $textClass }} flex-shrink-0">
        {{ strtoupper(substr($user->name ?? '?', 0, 1)) }}
    </div>
@endif
