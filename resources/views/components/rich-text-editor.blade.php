@props(['id', 'wireModel', 'value' => ''])

<div wire:ignore>
    <trix-editor
        id="trix-{{ $id }}"
        class="trix-content bg-surface border border-outline rounded-lg"
        x-data
        x-init="
            $el.editor.loadHTML(@js($value));
            $el.addEventListener('trix-change', () => {
                $wire.set('{{ $wireModel }}', $el.innerHTML, false);
            });
        "
        x-on:rich-text-cleared.window="if ($event.detail.id === '{{ $id }}') { $el.editor.loadHTML(''); }"
    ></trix-editor>
</div>
