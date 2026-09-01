@props([
    'wireModel' => null,
    'xModel' => null,
    'debounce' => '300ms',
    'placeholder' => 'Search...',
    'compact' => false,
])

@php
    $sizeClasses = $compact ? 'h-9 px-space-md' : 'px-space-lg py-space-md';
    $textClasses = $compact ? 'font-body-sm text-body-sm' : 'font-body-md text-body-md';
@endphp

<div {{ $attributes->merge(['class' => "flex items-center gap-space-sm {$sizeClasses} border border-outline rounded-lg bg-surface focus-within:ring-2 focus-within:ring-primary/50"]) }}>
    <x-ui.icon name="search" class="text-on-surface-variant flex-shrink-0" />
    <input
        type="text"
        placeholder="{{ $placeholder }}"
        @if ($wireModel)
            wire:model.live.debounce.{{ $debounce }}="{{ $wireModel }}"
        @elseif ($xModel)
            x-model="{{ $xModel }}"
        @endif
        class="w-full border-0 bg-transparent {{ $textClasses }} focus:outline-none focus:ring-0"
    />
</div>
