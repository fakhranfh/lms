@section('title', $pageTitle)

<div class="space-y-space-lg">
    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $pageTitle }}</h1>
    </div>

    @if (app()->isLocal())
        <div class="bg-tertiary-container border border-outline-variant rounded-lg p-space-md flex items-center justify-between">
            <p class="font-body-sm text-body-sm text-on-tertiary-container">Dev tools</p>
            <button
                type="button"
                wire:click="devAutofill"
                wire:loading.attr="disabled"
                wire:target="devAutofill"
                class="px-space-md py-space-xs bg-tertiary text-on-tertiary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
            >
                <span wire:loading wire:target="devAutofill" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                Autofill
            </button>
        </div>
    @endif

    <form
        wire:submit="save"
        x-data="{ submitting: false }"
        @submit="submitting = true"
        @assessmentform-error.window="submitting = false"
        class="space-y-space-lg"
    >
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

            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Session (optional)</label>
                <select wire:model="sessionId" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">None</option>
                    @foreach ($sessions as $index => $session)
                        <option value="{{ $session->id }}">Session {{ $index + 1 }} &mdash; {{ $session->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Questions -->
        @if ($assessmentType->value !== 'attendance')
        <div class="space-y-space-md">
            <div class="flex items-center justify-between">
                <h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>
                <button
                    type="button"
                    @click="window.addSyllabusRow($wire, 'question-template', 'questions', { id: null, description: '', points: '', selectedMaterialIds: [], materialSearch: '' })"
                    class="text-primary text-body-sm font-medium hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Question
                </button>
            </div>
            @error('questions') <p class="text-body-xs text-error">{{ $message }}</p> @enderror

            @foreach ($questions as $index => $question)
                @continue($question === null)
                <div wire:key="question-{{ $index }}" data-row class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">Question {{ $index + 1 }}</p>
                        <button type="button" @click="window.removeSyllabusRow($wire, 'questions.{{ $index }}', $el)" class="text-error text-body-sm hover:underline">Remove</button>
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

                        <input
                            type="text"
                            wire:model.live.debounce.400ms="questions.{{ $index }}.materialSearch"
                            placeholder="Search media library..."
                            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50 mb-space-xs"
                        />

                        <div
                            wire:loading.delay.class.remove="hidden"
                            wire:target="questions.{{ $index }}.materialSearch"
                            class="hidden max-h-40 overflow-y-auto border border-outline-variant rounded-lg divide-y divide-outline-variant animate-pulse"
                        >
                            @for ($i = 0; $i < 3; $i++)
                                <div class="flex items-center gap-space-sm px-space-md py-space-sm">
                                    <div class="w-4 h-4 rounded bg-surface-container"></div>
                                    <div class="h-3 bg-surface-container rounded w-1/2"></div>
                                </div>
                            @endfor
                        </div>

                        <div wire:loading.remove wire:target="questions.{{ $index }}.materialSearch" class="max-h-40 overflow-y-auto border border-outline-variant rounded-lg divide-y divide-outline-variant">
                            @forelse ($mediaByRow[$index] as $material)
                                <label class="flex items-center gap-space-sm px-space-md py-space-sm text-body-sm cursor-pointer hover:bg-surface-container">
                                    <input
                                        type="checkbox"
                                        @checked(in_array($material->id, $question['selectedMaterialIds']))
                                        @change="window.toggleWireArrayValue($wire, 'questions.{{ $index }}.selectedMaterialIds', '{{ $material->id }}')"
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

            <template id="question-template">
                <div data-row class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">New question</p>
                        <button type="button" @click="window.removeSyllabusRow($wire, 'questions.__NEW__', $el)" class="text-error text-body-sm hover:underline">Remove</button>
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <x-rich-text-editor id="question-__NEW__" wire-model="questions.__NEW__.description" />
                    </div>

                    <div class="w-40">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Points</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            @input="$wire.set('questions.__NEW__.points', $event.target.value, false)"
                            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                        />
                    </div>

                    <p class="font-body-xs text-body-xs text-on-surface-variant">Save the assessment to attach materials to this question.</p>
                </div>
            </template>
        </div>
        @endif

        <div class="flex gap-space-md">
            <a href="{{ route('assessments.index', $course) }}" class="flex-1 text-center px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                Cancel
            </a>
            <x-ui.submit-button label="Save" loadingLabel="Saving..." target="save" />
        </div>
    </form>
</div>
</div>
