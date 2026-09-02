@section('title', $pageTitle)

<div class="space-y-space-lg">
    <div>
        @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

        <!-- Header -->
        <div class="mt-space-xl mb-space-xl">
            <h1 class="font-headline-md text-headline-md text-on-surface">{{ $pageTitle }}</h1>
        </div>

        @if (app()->isLocal())
            <div class="mb-space-lg px-gutter py-space-md bg-secondary/10 border border-secondary/20 rounded-lg flex items-center gap-space-md">
                <span class="material-symbols-outlined text-secondary text-[20px]">science</span>
                <p class="font-body-sm text-body-sm text-secondary flex-1">Dev only: autofill the syllabus with fake data, including attached materials.</p>
                <button
                    type="button"
                    wire:click="devAutofill"
                    wire:loading.attr="disabled"
                    wire:target="devAutofill"
                    class="px-space-md py-space-xs rounded-lg bg-secondary text-on-secondary font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50"
                >
                    Autofill
                </button>
            </div>
        @endif

        <!-- Section nav -->
        <x-syllabus.section-nav
            id-prefix="section-"
            :sections="[
                'course_description' => 'Course Description',
                'class_policies' => 'Class Policies',
                'submission_and_collection' => 'Submission & Collection',
                'tutorial_activity_plan' => 'Tutorial Activity Plan',
                'learning_outcomes' => 'Learning Outcomes',
                'evaluation' => 'Evaluation',
                'assessment_rubric' => 'Assessment Rubric',
                'teaching_learning_strategies' => 'Teaching & Learning Strategies',
                'textbooks' => 'Textbooks',
                'competency_map' => 'Competency Map',
                'video_overview' => 'Video Overview',
            ]"
        />

        <form wire:submit="save" class="space-y-16">
            <!-- 1. Course Description -->
            <section id="section-course_description" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Course Description</h2>
                <x-rich-text-editor id="course-description" wire-model="courseDescription" :value="$courseDescription" :allow-attachments="false" />
                @error('courseDescription') <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p> @enderror

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'course_description', 'label' => 'Course Description'])
            </section>

            <!-- 2. Class Policies -->
            <section id="section-class_policies" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Class Policies</h2>
                <div class="space-y-space-sm">
                    @foreach ($classPolicies as $index => $policy)
                        @continue($policy === null)
                        <div wire:key="class-policy-{{ $index }}" data-row class="flex gap-space-sm items-start">
                            <select wire:model="classPolicies.{{ $index }}.scope" class="w-40 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg">
                                @foreach ($policyScopes as $scope)
                                    <option value="{{ $scope->value }}">{{ str($scope->value)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <div class="flex-1">
                                <x-rich-text-editor id="class-policy-{{ $index }}" wire-model="classPolicies.{{ $index }}.content" :value="$policy['content']" :allow-attachments="false" />
                            </div>
                            <button type="button" @click="window.removeSyllabusRow($wire, 'classPolicies.{{ $index }}', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("classPolicies.{$index}.content") <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    @endforeach
                    <template id="class-policy-template">
                        <div data-row class="flex gap-space-sm items-start">
                            <select @change="$wire.set('classPolicies.__NEW__.scope', $event.target.value, false)" class="w-40 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg">
                                @foreach ($policyScopes as $scope)
                                    <option value="{{ $scope->value }}" @selected($scope->value === 'general')>{{ str($scope->value)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <div class="flex-1">
                                <x-rich-text-editor id="class-policy-__NEW__" wire-model="classPolicies.__NEW__.content" :allow-attachments="false" />
                            </div>
                            <button type="button" @click="window.removeSyllabusRow($wire, 'classPolicies.__NEW__', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </template>
                </div>
                <button
                    type="button"
                    @click="window.addSyllabusRow($wire, 'class-policy-template', 'classPolicies', { scope: 'general', content: '' })"
                    class="mt-space-sm text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span> Add Policy
                </button>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'class_policies', 'label' => 'Class Policies'])
            </section>

            <!-- 3. Submission & Collection -->
            <section id="section-submission_and_collection" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Submission & Collection</h2>
                <x-rich-text-editor id="submission-and-collection" wire-model="submissionAndCollection" :value="$submissionAndCollection" :allow-attachments="false" />

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'submission_and_collection', 'label' => 'Submission & Collection'])
            </section>

            <!-- 4. Tutorial Activity Plan -->
            <section id="section-tutorial_activity_plan" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Tutorial Activity Plan</h2>
                <x-rich-text-editor id="tutorial-activity-plan" wire-model="tutorialActivityPlan" :value="$tutorialActivityPlan" :allow-attachments="false" />

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'tutorial_activity_plan', 'label' => 'Tutorial Activity Plan'])
            </section>

            <!-- 5. Learning Outcomes -->
            <section id="section-learning_outcomes" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Learning Outcomes</h2>
                @error('learningOutcomes') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                <div class="space-y-space-sm">
                    @foreach ($learningOutcomes as $index => $lo)
                        @continue($lo === null)
                        <div wire:key="learning-outcome-{{ $index }}" data-row class="flex gap-space-sm items-start">
                            <input
                                type="text"
                                wire:model="learningOutcomes.{{ $index }}.code"
                                placeholder="Code (e.g. LO1)"
                                class="w-32 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg"
                            />
                            <div class="flex-1">
                                <x-rich-text-editor id="learning-outcome-{{ $index }}" wire-model="learningOutcomes.{{ $index }}.description" :value="$lo['description']" :allow-attachments="false" />
                            </div>
                            <button type="button" @click="window.removeSyllabusRow($wire, 'learningOutcomes.{{ $index }}', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("learningOutcomes.{$index}.code") <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                        @error("learningOutcomes.{$index}.description") <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    @endforeach
                    <template id="learning-outcome-template">
                        <div data-row class="flex gap-space-sm items-start">
                            <input
                                type="text"
                                @input="$wire.set('learningOutcomes.__NEW__.code', $event.target.value, false)"
                                placeholder="Code (e.g. LO1)"
                                class="w-32 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg"
                            />
                            <div class="flex-1">
                                <x-rich-text-editor id="learning-outcome-__NEW__" wire-model="learningOutcomes.__NEW__.description" :allow-attachments="false" />
                            </div>
                            <button type="button" @click="window.removeSyllabusRow($wire, 'learningOutcomes.__NEW__', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </template>
                </div>
                <button
                    type="button"
                    @click="window.addSyllabusRow($wire, 'learning-outcome-template', 'learningOutcomes', { code: '', description: '' })"
                    class="mt-space-sm text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span> Add Learning Outcome
                </button>
            </section>

            <!-- 6. Evaluation -->
            <section id="section-evaluation" class="space-y-space-md">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Evaluation</h2>
                @foreach ($evaluations as $groupIndex => $group)
                    @continue($group === null)
                    <div wire:key="evaluation-group-{{ $groupIndex }}" data-row class="p-space-lg border border-outline-variant rounded-lg space-y-space-sm">
                        <div class="flex items-center gap-space-sm">
                            <input
                                type="text"
                                wire:model="evaluations.{{ $groupIndex }}.class_type"
                                placeholder="Class type (e.g. Quiz)"
                                class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                            />
                            <button type="button" @click="window.removeSyllabusRow($wire, 'evaluations.{{ $groupIndex }}', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("evaluations.{$groupIndex}.activities") <p class="text-body-sm text-error">{{ $message }}</p> @enderror

                        @foreach ($group['activities'] as $activityIndex => $activity)
                            @continue($activity === null)
                            <div wire:key="evaluation-activity-{{ $groupIndex }}-{{ $activityIndex }}" data-row class="flex gap-space-sm items-start pl-space-lg">
                                <input
                                    type="text"
                                    wire:model="evaluations.{{ $groupIndex }}.activities.{{ $activityIndex }}.activity"
                                    placeholder="Activity"
                                    class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                                />
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model="evaluations.{{ $groupIndex }}.activities.{{ $activityIndex }}.weight"
                                    placeholder="Weight %"
                                    class="w-28 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg"
                                />
                                <div class="flex-1 flex flex-wrap gap-space-sm">
                                    @foreach ($learningOutcomes as $loIndex => $lo)
                                        @continue($lo === null)
                                        <label class="inline-flex items-center gap-space-xs text-body-xs">
                                            <input
                                                type="checkbox"
                                                value="{{ $loIndex }}"
                                                wire:model="evaluations.{{ $groupIndex }}.activities.{{ $activityIndex }}.learning_outcome_indices"
                                            />
                                            {{ $lo['code'] }}
                                        </label>
                                    @endforeach
                                </div>
                                <button type="button" @click="window.removeSyllabusRow($wire, 'evaluations.{{ $groupIndex }}.activities.{{ $activityIndex }}', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        @endforeach

                        <template id="evaluation-activity-template-{{ $groupIndex }}">
                            <div data-row class="flex gap-space-sm items-start pl-space-lg">
                                <input
                                    type="text"
                                    @input="$wire.set('evaluations.{{ $groupIndex }}.activities.__NEW__.activity', $event.target.value, false)"
                                    placeholder="Activity"
                                    class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                                />
                                <input
                                    type="number"
                                    step="0.01"
                                    @input="$wire.set('evaluations.{{ $groupIndex }}.activities.__NEW__.weight', $event.target.value, false)"
                                    placeholder="Weight %"
                                    class="w-28 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg"
                                />
                                <div class="flex-1 flex flex-wrap gap-space-sm">
                                    @foreach ($learningOutcomes as $loIndex => $lo)
                                        @continue($lo === null)
                                        <label class="inline-flex items-center gap-space-xs text-body-xs">
                                            <input
                                                type="checkbox"
                                                @change="window.toggleWireArrayValue($wire, 'evaluations.{{ $groupIndex }}.activities.__NEW__.learning_outcome_indices', {{ $loIndex }})"
                                            />
                                            {{ $lo['code'] }}
                                        </label>
                                    @endforeach
                                </div>
                                <button type="button" @click="window.removeSyllabusRow($wire, 'evaluations.{{ $groupIndex }}.activities.__NEW__', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        </template>

                        <button
                            type="button"
                            @click="window.addSyllabusRow($wire, 'evaluation-activity-template-{{ $groupIndex }}', 'evaluations.{{ $groupIndex }}.activities', { activity: '', weight: '', learning_outcome_indices: [] })"
                            class="ml-space-lg text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span> Add Activity
                        </button>
                    </div>
                @endforeach

                <template id="evaluation-group-template">
                    <div data-row class="p-space-lg border border-outline-variant rounded-lg space-y-space-sm">
                        <div class="flex items-center gap-space-sm">
                            <input
                                type="text"
                                @input="$wire.set('evaluations.__NEW__.class_type', $event.target.value, false)"
                                placeholder="Class type (e.g. Quiz)"
                                class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                            />
                            <button type="button" @click="window.removeSyllabusRow($wire, 'evaluations.__NEW__', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <template id="evaluation-activity-template-__GROUP__">
                            <div data-row class="flex gap-space-sm items-start pl-space-lg">
                                <input
                                    type="text"
                                    @input="$wire.set('evaluations.__GROUP__.activities.__ACTIVITY__.activity', $event.target.value, false)"
                                    placeholder="Activity"
                                    class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                                />
                                <input
                                    type="number"
                                    step="0.01"
                                    @input="$wire.set('evaluations.__GROUP__.activities.__ACTIVITY__.weight', $event.target.value, false)"
                                    placeholder="Weight %"
                                    class="w-28 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg"
                                />
                                <div class="flex-1 flex flex-wrap gap-space-sm">
                                    @foreach ($learningOutcomes as $loIndex => $lo)
                                        @continue($lo === null)
                                        <label class="inline-flex items-center gap-space-xs text-body-xs">
                                            <input
                                                type="checkbox"
                                                @change="window.toggleWireArrayValue($wire, 'evaluations.__GROUP__.activities.__ACTIVITY__.learning_outcome_indices', {{ $loIndex }})"
                                            />
                                            {{ $lo['code'] }}
                                        </label>
                                    @endforeach
                                </div>
                                <button type="button" @click="window.removeSyllabusRow($wire, 'evaluations.__GROUP__.activities.__ACTIVITY__', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        </template>

                        <button
                            type="button"
                            @click="window.addSyllabusRow($wire, 'evaluation-activity-template-__GROUP__', 'evaluations.__GROUP__.activities', { activity: '', weight: '', learning_outcome_indices: [] })"
                            class="ml-space-lg text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span> Add Activity
                        </button>
                    </div>
                </template>

                <button
                    type="button"
                    @click="window.addSyllabusRow($wire, 'evaluation-group-template', 'evaluations', { class_type: '', activities: [] })"
                    class="text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span> Add Evaluation Group
                </button>
            </section>

            <!-- 7. Assessment Rubric -->
            <section id="section-assessment_rubric" class="space-y-space-md">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Assessment Rubric</h2>

                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant mb-space-sm">Proficiency Levels</p>
                    @foreach ($rubricProficiencyLevels as $index => $level)
                        @continue($level === null)
                        <div wire:key="proficiency-level-{{ $index }}" data-row class="flex gap-space-sm items-start mb-space-sm">
                            <input type="text" wire:model="rubricProficiencyLevels.{{ $index }}.label" placeholder="Label" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg" />
                            <input type="number" wire:model="rubricProficiencyLevels.{{ $index }}.score_min" placeholder="Min" class="w-24 px-space-md py-space-sm border border-outline rounded-lg" />
                            <input type="number" wire:model="rubricProficiencyLevels.{{ $index }}.score_max" placeholder="Max" class="w-24 px-space-md py-space-sm border border-outline rounded-lg" />
                            <button type="button" @click="window.removeSyllabusRow($wire, 'rubricProficiencyLevels.{{ $index }}', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    @endforeach
                    <template id="proficiency-level-template">
                        <div data-row class="flex gap-space-sm items-start mb-space-sm">
                            <input type="text" @input="$wire.set('rubricProficiencyLevels.__NEW__.label', $event.target.value, false)" placeholder="Label" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg" />
                            <input type="number" @input="$wire.set('rubricProficiencyLevels.__NEW__.score_min', $event.target.value, false)" placeholder="Min" class="w-24 px-space-md py-space-sm border border-outline rounded-lg" />
                            <input type="number" @input="$wire.set('rubricProficiencyLevels.__NEW__.score_max', $event.target.value, false)" placeholder="Max" class="w-24 px-space-md py-space-sm border border-outline rounded-lg" />
                            <button type="button" @click="window.removeSyllabusRow($wire, 'rubricProficiencyLevels.__NEW__', $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    </template>
                    <button
                        type="button"
                        @click="window.addSyllabusRow($wire, 'proficiency-level-template', 'rubricProficiencyLevels', { label: '', score_min: '', score_max: '' })"
                        class="text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span> Add Proficiency Level
                    </button>
                </div>

                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant mb-space-sm">Key Indicators</p>
                    @foreach ($rubricKeyIndicators as $index => $ki)
                        @continue($ki === null)
                        <div wire:key="key-indicator-{{ $index }}" data-row class="p-space-md border border-outline-variant rounded-lg space-y-space-sm mb-space-sm">
                            <div class="flex gap-space-sm items-start">
                                <select wire:model="rubricKeyIndicators.{{ $index }}.learning_outcome_index" class="w-40 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg">
                                    <option value="">Select LO</option>
                                    @foreach ($learningOutcomes as $loIndex => $lo)
                                        @continue($lo === null)
                                        <option value="{{ $loIndex }}">{{ $lo['code'] }}</option>
                                    @endforeach
                                </select>
                                <input type="text" wire:model="rubricKeyIndicators.{{ $index }}.code" placeholder="Code" class="w-32 px-space-md py-space-sm border border-outline rounded-lg" />
                                <input type="text" wire:model="rubricKeyIndicators.{{ $index }}.description" placeholder="Description" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg" />
                                <button type="button" @click="window.removeSyllabusRow($wire, ['rubricKeyIndicators.{{ $index }}', 'rubricCells.{{ $index }}'], $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                            @error("rubricKeyIndicators.{$index}.learning_outcome_index") <p class="text-body-sm text-error">{{ $message }}</p> @enderror

                            <div class="space-y-space-sm">
                                @foreach ($rubricProficiencyLevels as $plIndex => $level)
                                    @continue($level === null)
                                    <div>
                                        <label class="text-body-xs text-on-surface-variant">{{ $level['label'] }}</label>
                                        <x-rich-text-editor id="rubric-cell-{{ $index }}-{{ $plIndex }}" wire-model="rubricCells.{{ $index }}.{{ $plIndex }}" :value="$rubricCells[$index][$plIndex] ?? ''" :allow-attachments="false" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <template id="key-indicator-template">
                        <div data-row class="p-space-md border border-outline-variant rounded-lg space-y-space-sm mb-space-sm">
                            <div class="flex gap-space-sm items-start">
                                <select @change="$wire.set('rubricKeyIndicators.__NEW__.learning_outcome_index', $event.target.value, false)" class="w-40 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg">
                                    <option value="">Select LO</option>
                                    @foreach ($learningOutcomes as $loIndex => $lo)
                                        @continue($lo === null)
                                        <option value="{{ $loIndex }}">{{ $lo['code'] }}</option>
                                    @endforeach
                                </select>
                                <input type="text" @input="$wire.set('rubricKeyIndicators.__NEW__.code', $event.target.value, false)" placeholder="Code" class="w-32 px-space-md py-space-sm border border-outline rounded-lg" />
                                <input type="text" @input="$wire.set('rubricKeyIndicators.__NEW__.description', $event.target.value, false)" placeholder="Description" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg" />
                                <button type="button" @click="window.removeSyllabusRow($wire, ['rubricKeyIndicators.__NEW__', 'rubricCells.__NEW__'], $el)" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>

                            <div class="space-y-space-sm">
                                @foreach ($rubricProficiencyLevels as $plIndex => $level)
                                    @continue($level === null)
                                    <div>
                                        <label class="text-body-xs text-on-surface-variant">{{ $level['label'] }}</label>
                                        <x-rich-text-editor id="rubric-cell-__NEW__-{{ $plIndex }}" wire-model="rubricCells.__NEW__.{{ $plIndex }}" :allow-attachments="false" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </template>
                    <button
                        type="button"
                        @click="window.addSyllabusRow($wire, 'key-indicator-template', 'rubricKeyIndicators', { learning_outcome_index: '', code: '', description: '' })"
                        class="text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span> Add Key Indicator
                    </button>
                </div>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'assessment_rubric', 'label' => 'Assessment Rubric'])
            </section>

            <!-- 8. Teaching & Learning Strategies -->
            <section id="section-teaching_learning_strategies" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Teaching & Learning Strategies</h2>
                <x-rich-text-editor id="teaching-learning-strategies" wire-model="teachingLearningStrategies" :value="$teachingLearningStrategies" :allow-attachments="false" />

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'teaching_learning_strategies', 'label' => 'Teaching & Learning Strategies'])
            </section>

            <!-- 9. Textbooks -->
            <section id="section-textbooks" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Textbooks</h2>
                <x-rich-text-editor id="textbooks" wire-model="textbooks" :value="$textbooks" :allow-attachments="false" />

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'textbooks', 'label' => 'Textbooks'])
            </section>

            <!-- 10. Competency Map -->
            <section id="section-competency_map" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Competency Map</h2>
                <x-rich-text-editor id="competency-map" wire-model="competencyMap" :value="$competencyMap" :allow-attachments="false" />

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'competency_map', 'label' => 'Competency Map'])
            </section>

            <!-- 11. Video Overview -->
            <section id="section-video_overview" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-lg font-bold text-on-surface">Video Overview</h2>
                <x-rich-text-editor id="video-overview" wire-model="videoOverview" :value="$videoOverview" :allow-attachments="false" />

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'video_overview', 'label' => 'Video Overview'])
            </section>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ route('syllabus.index', $course) }}"
                    wire:navigate
                    class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-60 inline-flex items-center justify-center gap-space-sm"
                >
                    <span wire:loading wire:target="save" class="inline-block animate-spin">⟳</span>
                    <span wire:loading.remove wire:target="save">Update Syllabus</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
