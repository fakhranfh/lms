@section('title', $pageTitle)

<div class="max-w-3xl space-y-space-lg">
    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $pageTitle }}</h1>
    </div>

    <form wire:submit="save" class="space-y-space-lg">
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Title</label>
                <input type="text" wire:model="title" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                @error('title') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Weight (%)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="weight" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('weight') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Status</label>
                    <select wire:model="status" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ str($statusOption->value)->title() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Start Date</label>
                    <input type="datetime-local" wire:model="startDate" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('startDate') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">End Date</label>
                    <input type="datetime-local" wire:model="endDate" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('endDate') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Period</label>
                    <select wire:model="periodId" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Select a period</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->title }}</option>
                        @endforeach
                    </select>
                    @error('periodId') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Exam Type</label>
                    <select wire:model="examType" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                        @foreach ($examTypes as $examTypeOption)
                            <option value="{{ $examTypeOption->value }}">{{ str($examTypeOption->value)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    @error('examType') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Instructions</label>
                <p class="text-body-xs text-on-surface-variant mb-space-xs">Shown to students before they start this exam. Tailor it to this exam type (e.g. what materials are allowed).</p>
                <x-rich-text-editor id="final-exam-instructions" wire-model="instructions" :value="$instructions" />
                @error('instructions') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Questions -->
        <div class="space-y-space-md">
            <div class="flex items-center justify-between">
                <h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>
                <button type="button" wire:click="addQuestion" class="text-primary text-body-sm font-medium hover:underline inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Question
                </button>
            </div>
            @error('questions') <p class="text-body-xs text-error">{{ $message }}</p> @enderror

            @foreach ($questions as $index => $question)
                <div wire:key="question-{{ $index }}" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">Question {{ $index + 1 }}</p>
                        @if (count($questions) > 1)
                            <button type="button" wire:click="removeQuestion({{ $index }})" class="text-error text-body-sm hover:underline">Remove</button>
                        @endif
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <x-rich-text-editor id="question-{{ $index }}" wire-model="questions.{{ $index }}.description" :value="$question['description']" />
                        @error("questions.{$index}.description") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="w-40">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Points</label>
                        <input type="number" step="0.01" min="0" wire:model="questions.{{ $index }}.points" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                        @error("questions.{$index}.points") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Attachments</label>

                        @if ($selectedMediaByRow[$index]->isNotEmpty())
                            <div class="flex flex-wrap gap-space-xs mb-space-sm">
                                @foreach ($selectedMediaByRow[$index] as $material)
                                    <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full bg-surface-container text-body-xs text-on-surface">
                                        {{ $material->title }}
                                        <button type="button" wire:click="toggleQuestionMaterial({{ $index }}, '{{ $material->id }}')" class="text-error">&times;</button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <input
                            type="text"
                            wire:model.live.debounce.400ms="questions.{{ $index }}.materialSearch"
                            placeholder="Search media library..."
                            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50 mb-space-xs"
                        />

                        <div class="max-h-40 overflow-y-auto border border-outline-variant rounded-lg divide-y divide-outline-variant">
                            @forelse ($mediaByRow[$index] as $material)
                                <label class="flex items-center gap-space-sm px-space-md py-space-sm text-body-sm cursor-pointer hover:bg-surface-container">
                                    <input
                                        type="checkbox"
                                        @checked(in_array($material->id, $question['selectedMaterialIds']))
                                        wire:click="toggleQuestionMaterial({{ $index }}, '{{ $material->id }}')"
                                    />
                                    {{ $material->title }}
                                </label>
                            @empty
                                <p class="px-space-md py-space-sm text-body-sm text-on-surface-variant">No materials found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-space-md">
            <a href="{{ route('assessments.index', $course) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                Cancel
            </a>
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
