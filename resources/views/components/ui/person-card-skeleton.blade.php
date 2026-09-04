{{--
    Atom: a single skeleton person card — avatar circle + name line — sized
    to match a real card inside <x-ui.person-grid>. Composed of
    <x-ui.skeleton-box> atoms.
--}}
<div {{ $attributes->merge(['class' => 'bg-surface p-space-lg flex flex-col items-center gap-space-sm']) }}>
    <x-ui.skeleton-box class="w-12 h-12 rounded-full" />
    <x-ui.skeleton-box class="h-4 w-2/3" />
</div>
