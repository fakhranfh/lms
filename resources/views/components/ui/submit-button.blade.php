{{--
    Molecule: submit button with a double-submit guard.

    Must be rendered inside a <form> that wires up the guard itself
    (see course-form.blade.php / session-form.blade.php):
        x-data="{ submitting: false }"
        @submit="submitting = true"
        @<event>.window="submitting = false"
    where <event> is dispatched by the Livewire component's save()
    action when it fails, so a thrown validation/server error
    re-enables the button instead of leaving it stuck disabled.
--}}
@props([
    'label',
    'loadingLabel' => 'Saving...',
    'target' => 'save',
])

{{--
    :disabled is the sole source of truth for the disabled state — it
    stays true from submit until the redirect actually navigates away
    (or an error resets it). Don't add wire:loading.attr="disabled"
    here: Livewire clears that attribute the moment its request
    settles, which can race ahead of the redirect and briefly
    re-enable the button, letting a fast double-click slip through.
--}}
<button
    type="submit"
    :disabled="submitting"
    {{ $attributes->merge(['class' => 'flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-60 inline-flex items-center justify-center gap-space-sm']) }}
>
    <span wire:loading wire:target="{{ $target }}">
        <x-ui.spinner />
    </span>
    <span wire:loading.remove wire:target="{{ $target }}">{{ $label }}</span>
    <span wire:loading wire:target="{{ $target }}">{{ $loadingLabel }}</span>
</button>
