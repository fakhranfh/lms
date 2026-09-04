{{--
    Molecule: responsive grid container for person/avatar cards (students,
    teachers, submissions), joined by hairline gaps via bg-outline-variant.
    Wrap card items in it; each item should carry its own `bg-surface`
    background so the gap reads as a divider. Composed of <x-ui.skeleton-box>
    atoms when used by <x-ui.person-grid-skeleton>.
--}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant']) }}>
    {{ $slot }}
</div>
