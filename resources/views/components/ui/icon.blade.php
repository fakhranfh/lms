@props([
    'name',
    'size' => 18,
])

<span {{ $attributes->merge(['class' => "material-symbols-outlined text-[{$size}px] leading-none"]) }}>{{ $name }}</span>
