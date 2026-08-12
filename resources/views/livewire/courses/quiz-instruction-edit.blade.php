@section('title', 'Quiz Instructions')

<div class="max-w-3xl space-y-space-lg">
    <div>
        <h1 class="font-headline-md text-headline-md text-on-surface">Quiz Instructions</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">This page is shown to every student before they start any quiz, across all courses.</p>
    </div>

    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-space-lg">
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Content</label>
                <x-rich-text-editor id="quiz-instructions" wire-model="content" :value="$content" />
                @error('content') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>

            @if ($instruction)
                <p class="text-body-xs text-on-surface-variant">
                    Last updated by {{ $instruction->updater?->name ?? 'Unknown' }} on {{ $instruction->updated_at->format('M j, Y, H:i') }}
                </p>
            @endif
        </div>

        <div class="flex gap-space-md">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span wire:loading wire:target="save" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Save
            </button>
        </div>
    </form>
</div>
