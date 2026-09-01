@props([
    'size' => 18,
])

<span {{ $attributes->merge(['class' => "inline-block animate-spin text-[{$size}px] leading-none"]) }}>⟳</span>
