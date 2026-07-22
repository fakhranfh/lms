@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="w-full max-w-3xl mx-auto space-y-space-lg">
        @if ($errorMessage)
            <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
                <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
                <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
            </div>
        @endif

        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">{{ $assignment->title }}</h1>
        </div>

        <div class="border border-outline rounded-lg p-space-lg space-y-space-md">
            <p class="text-body-md text-on-surface whitespace-pre-line">{{ $assignment->prompt_question }}</p>

            @if (!empty($assignment->rubricItems()))
                <div class="pt-space-md border-t border-outline/30">
                    <h2 class="text-label-md font-label-md text-on-surface-variant mb-space-sm">Rubric</h2>
                    <ul class="space-y-space-xs">
                        @foreach ($assignment->rubricItems() as $item)
                            <li class="text-body-sm text-on-surface-variant">
                                <span class="font-medium text-on-surface">{{ $item['criterion'] ?? '' }}</span>
                                ({{ $item['weight'] ?? 0 }}% &middot; {{ $item['max_points'] ?? 0 }} pts)
                                @if (!empty($item['description']))
                                    &mdash; {{ $item['description'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <form wire:submit="submit" class="space-y-space-md">
            <div>
                <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Your Answer</label>
                <textarea wire:model="studentAnswer" rows="10" class="w-full px-space-md py-space-sm border border-outline rounded-lg"></textarea>
                @error('studentAnswer') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50">
                Submit
            </button>
        </form>
    </div>
</div>
