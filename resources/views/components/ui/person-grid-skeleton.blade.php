{{--
    Organism: a <x-ui.person-grid> of <x-ui.person-card-skeleton> atoms,
    matching the People/Submissions person card grid. Use while data is
    loading (wire:loading or wire:init) instead of showing a stale list.
--}}
@props(['rows' => 9])

<x-ui.person-grid {{ $attributes->merge(['class' => 'animate-pulse']) }}>
    @for ($i = 0; $i < $rows; $i++)
        <x-ui.person-card-skeleton />
    @endfor
</x-ui.person-grid>
