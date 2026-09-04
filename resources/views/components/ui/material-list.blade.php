@props(['files'])

<div {{ $attributes->merge(['class' => 'rte-content flex flex-wrap gap-2']) }} x-data="rteVideoPreview()" @click="onContentClick($event)">
    @foreach ($files as $file)
        <x-ui.material-chip :file="$file" />
    @endforeach

    <x-ui.material-preview-modals />
</div>
