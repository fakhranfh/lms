@section('title', 'Grade — '.$group->name)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div>
        <a href="{{ route('assessments.team.show', $assessment) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Submissions
        </a>
    </div>

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden" x-data="rteVideoPreview()" @click="onContentClick($event)">
        <div class="flex items-center gap-space-md px-space-lg py-space-md border-b border-outline-variant">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Grade &mdash; {{ $group->name }}</h2>
                @if ($attempt)
                    <p class="text-body-sm text-on-surface-variant">
                        Attempt {{ $attempt->attempt_number }} &middot; submitted by {{ $attempt->submitter?->name }} ({{ $attempt->submitted_at_display?->format('M j, Y H:i') }})
                    </p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2">
            <!-- Left: Group's Answer -->
            <div class="p-space-lg border-b md:border-b-0 md:border-r border-outline-variant">
                <h4 class="font-label-md text-label-md text-on-surface mb-space-md">Answer</h4>

                @if ($answer?->answer_text)
                    <div class="rte-content prose prose-sm max-w-none text-on-surface">{!! $answer->answer_text !!}</div>
                @else
                    <p class="text-body-sm text-on-surface-variant">No answer submitted.</p>
                @endif
            </div>

            <!-- Right: Grading Form -->
            <div class="p-space-lg">
                <form wire:submit="submitGrade" class="space-y-space-md">
                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Score</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="gradeScore"
                            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                        />
                        @error('gradeScore') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Feedback</label>
                        <textarea wire:model="gradeFeedback" rows="3" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                    </div>

                    <div class="flex gap-space-md">
                        <a href="{{ route('assessments.team.show', $assessment) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="submitGrade"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                        >
                            Save Grade
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <x-ui.material-preview-modals />
    </div>
</div>
