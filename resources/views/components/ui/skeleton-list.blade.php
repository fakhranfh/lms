{{--
    Organism: a card of stacked <x-ui.skeleton-row> molecules, matching the
    sessions list container. Use while a bulk/generate/delete action is
    wire:loading so the list shows a loading state instead of a stale one.
--}}
@props(['rows' => 4])

<div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
    @for ($i = 0; $i < $rows; $i++)
        <x-ui.skeleton-row />
    @endfor
</div>
