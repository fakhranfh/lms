{{--
    Molecule: file upload trigger + progress + error feedback.

    Must be rendered inside an Alpine scope that exposes the upload
    contract used across the app (see mediaLibraryUploader /
    materialPicker in the pages that use this): `uploading` (bool),
    `progress` (number), `statusText` (string), `clientError`
    (string|null) and an `upload(file)` method.
--}}
@props([
    'refName' => 'fileInput',
    'accept' => null,
    'label' => 'Upload File',
])

<div {{ $attributes->merge(['class' => 'p-space-lg bg-surface-container rounded-lg border-2 border-dashed border-outline text-center']) }}>
    <input
        type="file"
        x-ref="{{ $refName }}"
        @if ($accept) accept="{{ $accept }}" @endif
        @change="upload($refs.{{ $refName }}.files[0])"
        :disabled="uploading"
        class="hidden"
    />
    <button
        type="button"
        @click="$refs.{{ $refName }}.click()"
        :disabled="uploading"
        class="text-body-md text-primary font-medium hover:underline disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <span x-show="! uploading">{{ $label }}</span>
        <span x-show="uploading" x-cloak x-text="statusText + (statusText === 'Uploading...' ? ' (' + progress + '%)' : '')"></span>
    </button>

    <template x-if="uploading">
        <div class="mt-space-md">
            <x-ui.progress-bar />
        </div>
    </template>

    <template x-if="clientError">
        <p class="mt-space-sm text-body-sm text-error" x-text="clientError"></p>
    </template>
</div>
