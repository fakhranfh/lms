{{--
    Atom: rounded status pill. Pass `bg`/`text` (Tailwind color utility
    classes) and an optional `icon` (material-symbols name); the slot is the
    label. Used for assessment status and similar colored badges.
--}}
@props([
    'bg' => 'bg-on-surface-variant/10',
    'text' => 'text-on-surface-variant',
    'icon' => null,
])

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm {$bg} {$text}"]) }}>
    @if ($icon)
        <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span>
    @endif
    {{ $slot }}
</span>
