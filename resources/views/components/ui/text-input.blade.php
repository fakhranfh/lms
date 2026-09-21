@props([
    'icon' => null,
])

<div class="relative">
    @if ($icon)
        <x-ui.icon :name="$icon" size="20" class="absolute inset-y-0 left-3 flex items-center text-secondary/60" />
    @endif
    <input
        {{ $attributes->merge([
            'class' => 'w-full bg-surface-container-lowest border border-outline-variant text-on-surface font-body-md text-body-md rounded-lg py-space-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all '
                . ($icon ? 'pl-10 ' : 'pl-space-md ')
                . (isset($right) ? 'pr-12' : 'pr-space-md'),
        ]) }}
    />
    @isset($right)
        {{ $right }}
    @endisset
</div>
