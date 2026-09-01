@props([
    'value' => 'progress',
])

<div {{ $attributes->merge(['class' => 'w-full h-2 bg-surface rounded-full overflow-hidden']) }}>
    <div class="h-full bg-primary transition-all duration-150" :style="`width: ${ {{ $value }} }%`"></div>
</div>
