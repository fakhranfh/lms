{{--
    Molecule: one skeleton row shaped like a session accordion row (drag
    handle, checkbox, expand toggle, title/subtitle, reorder + action
    icons). Composed of <x-ui.skeleton-box> atoms.
--}}
<div class="p-space-lg flex items-center gap-space-md">
    <x-ui.skeleton-box class="w-5 h-5 flex-shrink-0" />
    <x-ui.skeleton-box class="w-4 h-4 flex-shrink-0" />
    <x-ui.skeleton-box class="w-9 h-9 flex-shrink-0" />
    <div class="flex-1 space-y-space-xs">
        <x-ui.skeleton-box class="h-4 w-1/3" />
        <x-ui.skeleton-box class="h-3 w-1/4" />
    </div>
    <x-ui.skeleton-box class="w-7 h-7 flex-shrink-0" />
    <x-ui.skeleton-box class="w-7 h-7 flex-shrink-0" />
    <x-ui.skeleton-box class="w-9 h-9 flex-shrink-0" />
    <x-ui.skeleton-box class="w-9 h-9 flex-shrink-0" />
</div>
