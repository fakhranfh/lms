@props(['show', 'onClose' => null, 'maxWidth' => 'max-w-[420px]', 'backdrop' => 'bg-black/50'])

{{--
    Teleported to <body> so the backdrop is never clipped by an ancestor
    that establishes its own containing block (a parent with a CSS
    transform, filter, etc. turns `position: fixed` descendants into
    something scoped to that ancestor instead of the real viewport).
    `show`/`onClose` are raw Alpine expressions evaluated in the caller's
    x-data scope, which teleport preserves regardless of where the
    rendered markup ends up in the DOM.
--}}
<template x-teleport="body">
    <div
        x-show="{{ $show }}"
        x-cloak
        @if ($onClose)
            @click.self="{{ $onClose }}"
            @keydown.escape.window="{{ $onClose }}"
        @endif
        class="fixed inset-0 z-[100] flex items-center justify-center {{ $backdrop }} px-gutter"
    >
        <div {{ $attributes->merge(['class' => "relative w-full {$maxWidth}"]) }}>
            {{ $slot }}
        </div>
    </div>
</template>
