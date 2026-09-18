@props(['maxWidth' => 'max-w-md'])

<div {{ $attributes->class(['fixed inset-0 z-10 overflow-y-auto bg-surface-container']) }}>
    @isset($topbar)
        {{ $topbar }}
    @endisset

    <div class="min-h-screen w-full flex items-center justify-center px-gutter py-space-xl {{ isset($topbar) ? 'pt-24' : '' }}">
        <div class="{{ $maxWidth }} w-full bg-surface border border-outline-variant rounded-lg p-space-xl space-y-space-lg">
            {{ $slot }}
        </div>
    </div>
</div>
