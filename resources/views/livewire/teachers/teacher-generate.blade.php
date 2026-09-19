@section('title', 'Generate Teachers')

<div class="w-full space-y-space-lg">
    <div @class([
        'space-y-space-lg',
        'bg-surface border border-outline-variant rounded-lg p-space-lg' => ! $embedded,
    ])>
        <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Generate Teachers</h2>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                Creates fake teacher accounts (for demo/testing), each with a one-time login link.
            </p>
        </div>

        <div>
            <label for="count" class="block font-label-md text-label-md text-on-surface mb-space-xs">Number of teachers</label>
            <input type="number" wire:model="count" id="count" min="1" max="200"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md border-outline-variant">
            @error('count')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="loginLinkTtlDays" class="block font-label-md text-label-md text-on-surface mb-space-xs">
                Login link valid for (days)
            </label>
            <input type="number" wire:model="loginLinkTtlDays" id="loginLinkTtlDays" min="1" max="365"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md border-outline-variant">
            @error('loginLinkTtlDays')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-space-md">
            <button type="button" wire:click="generate" wire:loading.attr="disabled" wire:target="generate"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                <svg wire:loading wire:target="generate" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="generate">Generate</span>
                <span wire:loading wire:target="generate">Generating...</span>
            </button>
            @if ($embedded)
                <button type="button" @click="showGenerateModal = false" class="font-label-md text-label-md text-secondary hover:underline">Cancel</button>
            @else
                <a href="{{ route('teachers.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
            @endif
        </div>
    </div>
</div>
