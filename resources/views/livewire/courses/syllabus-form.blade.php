@section('title', $pageTitle)

<div
    class="min-h-screen bg-background py-space-xl px-gutter"
    x-data="{
        active: 'course_description',
        goTo(key) {
            this.active = key;
            const target = document.getElementById('section-' + key);
            const scrollContainer = this.$el.closest('main');
            if (! target || ! scrollContainer) { return; }
            const navBottom = this.$refs.sectionNav.getBoundingClientRect().bottom;
            const targetTop = target.getBoundingClientRect().top + scrollContainer.scrollTop - navBottom - 16;
            scrollContainer.scrollTo({ top: targetTop, behavior: 'smooth' });
        },
    }"
>
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumb -->
        <div class="mb-space-lg">
            <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
                <a href="{{ route('courses.index') }}" class="hover:text-on-surface transition">Courses</a>
                <span>/</span>
                <a href="{{ route('syllabus.index', $course) }}" class="hover:text-on-surface transition">{{ $course->title }}</a>
                <span>/</span>
                <span class="text-on-surface font-medium">{{ $pageTitle }}</span>
            </nav>
        </div>

        <!-- Header -->
        <div class="mb-space-xl flex items-center justify-between">
            <div>
                <h1 class="font-headline-md text-headline-md text-on-surface">{{ $pageTitle }}</h1>
                <p class="text-body-md text-on-surface-variant mt-space-sm">in <strong>{{ $course->title }}</strong></p>
            </div>
            <a
                href="{{ route('syllabus.index', $course) }}"
                class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors flex-shrink-0"
            >
                Back to Syllabus
            </a>
        </div>

        <!-- Section nav -->
        <nav x-ref="sectionNav" class="relative sticky top-0 z-10 bg-background flex gap-space-sm overflow-x-auto pt-space-xxs pb-space-xs mb-space-lg border-b border-outline-variant before:content-[''] before:absolute before:left-0 before:right-0 before:-top-space-lg before:h-space-lg before:bg-background before:-z-10">
            @foreach ([
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
            ] as $sectionKey => $sectionLabel)
                <a
                    href="#section-{{ $sectionKey }}"
                    @click.prevent="goTo('{{ $sectionKey }}')"
                    class="px-space-md py-space-xs rounded-lg font-label-sm text-label-sm whitespace-nowrap transition-colors"
                    :class="active === '{{ $sectionKey }}' ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:text-on-surface'"
                >
                    {{ $sectionLabel }}
                </a>
            @endforeach
        </nav>

        <form wire:submit="save" class="space-y-space-2xl">
            <!-- 1. Course Description -->
            <section id="section-course_description" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Course Description</h2>
                <textarea
                    wire:model="courseDescription"
                    rows="4"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                    placeholder="Describe the course..."
                ></textarea>
                @error('courseDescription') <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p> @enderror

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'course_description', 'label' => 'Course Description'])
            </section>

            <!-- 2. Class Policies -->
            <section id="section-class_policies" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Class Policies</h2>
                <div class="space-y-space-sm">
                    @foreach ($classPolicies as $index => $policy)
                        <div wire:key="class-policy-{{ $index }}" class="flex gap-space-sm items-start">
                            <select wire:model="classPolicies.{{ $index }}.scope" class="w-40 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg">
                                @foreach ($policyScopes as $scope)
                                    <option value="{{ $scope->value }}">{{ str($scope->value)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <textarea
                                wire:model="classPolicies.{{ $index }}.content"
                                rows="2"
                                placeholder="Policy content"
                                class="flex-1 px-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                            ></textarea>
                            <button type="button" wire:click="removeClassPolicy({{ $index }})" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("classPolicies.{$index}.content") <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                <button type="button" wire:click="addClassPolicy" class="mt-space-sm text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[18px]">add</span> Add Policy
                </button>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'class_policies', 'label' => 'Class Policies'])
            </section>

            <!-- 3. Submission & Collection -->
            <section id="section-submission_and_collection" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Submission & Collection</h2>
                <textarea
                    wire:model="submissionAndCollection"
                    rows="4"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                ></textarea>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'submission_and_collection', 'label' => 'Submission & Collection'])
            </section>

            <!-- 4. Tutorial Activity Plan -->
            <section id="section-tutorial_activity_plan" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Tutorial Activity Plan</h2>
                <textarea
                    wire:model="tutorialActivityPlan"
                    rows="4"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                ></textarea>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'tutorial_activity_plan', 'label' => 'Tutorial Activity Plan'])
            </section>

            <!-- 5. Learning Outcomes -->
            <section id="section-learning_outcomes" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Learning Outcomes</h2>
                @error('learningOutcomes') <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                <div class="space-y-space-sm">
                    @foreach ($learningOutcomes as $index => $lo)
                        <div wire:key="learning-outcome-{{ $index }}" class="flex gap-space-sm items-start">
                            <input
                                type="text"
                                wire:model="learningOutcomes.{{ $index }}.code"
                                placeholder="Code (e.g. LO1)"
                                class="w-32 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg"
                            />
                            <textarea
                                wire:model="learningOutcomes.{{ $index }}.description"
                                rows="2"
                                placeholder="Description"
                                class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                            ></textarea>
                            <button type="button" wire:click="removeLearningOutcome({{ $index }})" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("learningOutcomes.{$index}.code") <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                        @error("learningOutcomes.{$index}.description") <p class="text-body-sm text-error">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                <button type="button" wire:click="addLearningOutcome" class="mt-space-sm text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[18px]">add</span> Add Learning Outcome
                </button>
            </section>

            <!-- 6. Evaluation -->
            <section id="section-evaluation" class="space-y-space-md">
                <h2 class="font-label-lg text-label-lg text-on-surface">Evaluation</h2>
                @foreach ($evaluations as $groupIndex => $group)
                    <div wire:key="evaluation-group-{{ $groupIndex }}" class="p-space-lg border border-outline-variant rounded-lg space-y-space-sm">
                        <div class="flex items-center gap-space-sm">
                            <input
                                type="text"
                                wire:model="evaluations.{{ $groupIndex }}.class_type"
                                placeholder="Class type (e.g. Quiz)"
                                class="flex-1 px-space-md py-space-sm border border-outline rounded-lg"
                            />
                            <button type="button" wire:click="removeEvaluationGroup({{ $groupIndex }})" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("evaluations.{$groupIndex}.activities") <p class="text-body-sm text-error">{{ $message }}</p> @enderror

                        @foreach ($group['activities'] as $activityIndex => $activity)
                            <div wire:key="evaluation-activity-{{ $groupIndex }}-{{ $activityIndex }}" class="flex gap-space-sm items-start pl-space-lg">
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
                                <button type="button" wire:click="removeEvaluationActivity({{ $groupIndex }}, {{ $activityIndex }})" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        @endforeach

                        <button
                            type="button"
                            wire:click="addEvaluationActivity({{ $groupIndex }})"
                            class="ml-space-lg text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span> Add Activity
                        </button>
                    </div>
                @endforeach
                <button type="button" wire:click="addEvaluationGroup" class="text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[18px]">add</span> Add Evaluation Group
                </button>
            </section>

            <!-- 7. Assessment Rubric -->
            <section id="section-assessment_rubric" class="space-y-space-md">
                <h2 class="font-label-lg text-label-lg text-on-surface">Assessment Rubric</h2>

                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant mb-space-sm">Proficiency Levels</p>
                    @foreach ($rubricProficiencyLevels as $index => $level)
                        <div wire:key="proficiency-level-{{ $index }}" class="flex gap-space-sm items-start mb-space-sm">
                            <input type="text" wire:model="rubricProficiencyLevels.{{ $index }}.label" placeholder="Label" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg" />
                            <input type="number" wire:model="rubricProficiencyLevels.{{ $index }}.score_min" placeholder="Min" class="w-24 px-space-md py-space-sm border border-outline rounded-lg" />
                            <input type="number" wire:model="rubricProficiencyLevels.{{ $index }}.score_max" placeholder="Max" class="w-24 px-space-md py-space-sm border border-outline rounded-lg" />
                            <button type="button" wire:click="removeProficiencyLevel({{ $index }})" class="p-2 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addProficiencyLevel" class="text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs">
                        <span class="material-symbols-outlined text-[18px]">add</span> Add Proficiency Level
                    </button>
                </div>

                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant mb-space-sm">Key Indicators</p>
                    @foreach ($rubricKeyIndicators as $index => $ki)
                        <div wire:key="key-indicator-{{ $index }}" class="p-space-md border border-outline-variant rounded-lg space-y-space-sm mb-space-sm">
                            <div class="flex gap-space-sm items-start">
                                <select wire:model="rubricKeyIndicators.{{ $index }}.learning_outcome_index" class="w-40 flex-shrink-0 px-space-md py-space-sm border border-outline rounded-lg">
                                    <option value="">Select LO</option>
                                    @foreach ($learningOutcomes as $loIndex => $lo)
                                        <option value="{{ $loIndex }}">{{ $lo['code'] }}</option>
                                    @endforeach
                                </select>
                                <input type="text" wire:model="rubricKeyIndicators.{{ $index }}.code" placeholder="Code" class="w-32 px-space-md py-space-sm border border-outline rounded-lg" />
                                <input type="text" wire:model="rubricKeyIndicators.{{ $index }}.description" placeholder="Description" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg" />
                                <button type="button" wire:click="removeKeyIndicator({{ $index }})" class="p-2 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                            @error("rubricKeyIndicators.{$index}.learning_outcome_index") <p class="text-body-sm text-error">{{ $message }}</p> @enderror

                            <div class="grid gap-space-sm" style="grid-template-columns: repeat({{ max(count($rubricProficiencyLevels), 1) }}, minmax(0, 1fr));">
                                @foreach ($rubricProficiencyLevels as $plIndex => $level)
                                    <div>
                                        <label class="text-body-xs text-on-surface-variant">{{ $level['label'] }}</label>
                                        <textarea
                                            wire:model="rubricCells.{{ $index }}.{{ $plIndex }}"
                                            rows="2"
                                            class="w-full px-space-sm py-space-xs border border-outline rounded-lg text-body-sm"
                                        ></textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addKeyIndicator" class="text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs">
                        <span class="material-symbols-outlined text-[18px]">add</span> Add Key Indicator
                    </button>
                </div>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'assessment_rubric', 'label' => 'Assessment Rubric'])
            </section>

            <!-- 8. Teaching & Learning Strategies -->
            <section id="section-teaching_learning_strategies" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Teaching & Learning Strategies</h2>
                <textarea wire:model="teachingLearningStrategies" rows="4" class="w-full px-space-lg py-space-md border border-outline rounded-lg"></textarea>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'teaching_learning_strategies', 'label' => 'Teaching & Learning Strategies'])
            </section>

            <!-- 9. Textbooks -->
            <section id="section-textbooks" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Textbooks</h2>
                <textarea wire:model="textbooks" rows="4" class="w-full px-space-lg py-space-md border border-outline rounded-lg"></textarea>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'textbooks', 'label' => 'Textbooks'])
            </section>

            <!-- 10. Competency Map -->
            <section id="section-competency_map" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Competency Map</h2>
                <textarea wire:model="competencyMap" rows="4" class="w-full px-space-lg py-space-md border border-outline rounded-lg"></textarea>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'competency_map', 'label' => 'Competency Map'])
            </section>

            <!-- 11. Video Overview -->
            <section id="section-video_overview" class="space-y-space-sm">
                <h2 class="font-label-lg text-label-lg text-on-surface">Video Overview</h2>
                <textarea wire:model="videoOverview" rows="4" class="w-full px-space-lg py-space-md border border-outline rounded-lg"></textarea>

                @include('livewire.courses.partials.syllabus-material-picker', ['section' => 'video_overview', 'label' => 'Video Overview'])
            </section>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ route('syllabus.index', $course) }}"
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
                    <span wire:loading.remove wire:target="save">{{ $syllabus ? 'Update Syllabus' : 'Create Syllabus' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('materialPicker', (config) => ({
                open: false,
                selectedItems: config.initialSelected || [],
                property: config.property || 'selectedMaterialIds',

                get selectedIds() {
                    return this.selectedItems.map((item) => item.id);
                },

                toggle(item) {
                    const index = this.selectedItems.findIndex((selected) => selected.id === item.id);

                    if (index >= 0) {
                        this.selectedItems.splice(index, 1);
                    } else {
                        this.selectedItems.push(item);
                    }

                    this.sync();
                },

                remove(id) {
                    this.selectedItems = this.selectedItems.filter((item) => item.id !== id);
                    this.sync();
                },

                sync() {
                    this.$wire.set(this.property, this.selectedIds, false);
                },
            }));
        });
    </script>
@endpush
