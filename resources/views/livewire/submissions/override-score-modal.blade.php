@section('title', 'Override Score')

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="w-full max-w-2xl mx-auto space-y-space-lg">
        @if (session('success'))
            <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
                <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
                <p class="font-body-md text-body-md text-success">{{ session('success') }}</p>
            </div>
        @endif

        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Override Score</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">{{ $submission->assignment->title }} &mdash; {{ $submission->user->name }}</p>
        </div>

        <div class="border border-outline rounded-lg p-space-lg space-y-space-sm">
            <p class="text-label-sm text-on-surface-variant">AI Score</p>
            <p class="text-headline-sm font-headline-sm text-on-surface">{{ $submission->ai_score ?? '—' }}</p>

            <p class="text-label-sm text-on-surface-variant mt-space-md">AI Feedback</p>
            <p class="text-body-sm text-on-surface whitespace-pre-line">{{ $submission->getDisplayFeedback() ?? 'No feedback available.' }}</p>
        </div>

        <form wire:submit="save" class="space-y-space-md">
            <div>
                <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Teacher Score</label>
                <input type="number" step="0.01" wire:model="teacherScore" class="w-full px-space-md py-space-sm border border-outline rounded-lg" />
                @error('teacherScore') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Teacher Feedback</label>
                <textarea wire:model="teacherFeedback" rows="5" class="w-full px-space-md py-space-sm border border-outline rounded-lg"></textarea>
                @error('teacherFeedback') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Save Override
            </button>
        </form>
    </div>
</div>
