{{--
    Atom: inline label with a filled check/close square, used to show
    whether a per-session requirement (attendance, forum participation) was
    met.
--}}
@props([
    'met' => false,
])

<span class="inline-flex items-center gap-1 text-body-sm {{ $met ? 'text-on-surface' : 'text-on-surface-variant' }}">
    {{ $slot }}
    <span class="material-symbols-outlined text-[16px] text-on-primary {{ $met ? 'bg-success' : 'bg-outline-variant' }} rounded-sm">{{ $met ? 'check' : 'close' }}</span>
</span>
