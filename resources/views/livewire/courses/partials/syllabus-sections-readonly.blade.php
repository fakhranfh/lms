<div
    class="space-y-space-lg"
    x-data="{
        active: 'course_description',
        goTo(key) {
            this.active = key;
            const target = document.getElementById('readonly-section-' + key);
            const scrollContainer = this.$el.closest('main');
            if (! target || ! scrollContainer) { return; }
            const navBottom = this.$refs.sectionNav.getBoundingClientRect().bottom;
            const targetTop = target.getBoundingClientRect().top + scrollContainer.scrollTop - navBottom - 16;
            scrollContainer.scrollTo({ top: targetTop, behavior: 'smooth' });
        },
    }"
>
    <!-- Section shortcuts -->
    <nav x-ref="sectionNav" class="relative sticky top-0 z-10 bg-background flex flex-wrap gap-space-sm pt-space-xxs pb-space-xs border-b border-outline-variant before:content-[''] before:absolute before:left-0 before:right-0 before:-top-space-lg before:h-space-lg before:bg-background before:-z-10">
        @foreach ([
            'course_description' => 'Course Description',
            'class_policies' => 'Class Policies',
            'submission_and_collection' => 'Submission and Collection of Assignment',
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
                href="#readonly-section-{{ $sectionKey }}"
                @click.prevent="goTo('{{ $sectionKey }}')"
                class="px-space-md py-space-xs rounded-lg font-label-sm text-label-sm whitespace-nowrap transition-colors"
                :class="active === '{{ $sectionKey }}' ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:text-on-surface'"
            >
                {{ $sectionLabel }}
            </a>
        @endforeach
    </nav>

    <div class="space-y-space-md">
    <!-- Course Description -->
    <div id="readonly-section-course_description" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Course Description</h3>
        @if ($syllabus->course_description)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->course_description !!}</div>
        @else
            <p class="text-body-md text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Class Policies -->
    <div id="readonly-section-class_policies" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
        <h3 class="font-label-lg text-label-lg text-on-surface">Class Policies</h3>

        @if ($classPoliciesByScope['f2f_video']->isEmpty() && $classPoliciesByScope['online']->isEmpty() && $classPoliciesByScope['general']->isEmpty())
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @else
            <div class="border border-outline-variant rounded-lg overflow-hidden">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th class="w-1/2 px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">F2F/ Video conference Session</th>
                            <th class="w-1/2 px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Online Session</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-outline-variant align-top">
                            <td class="px-space-lg py-space-md">
                                @if ($classPoliciesByScope['f2f_video']->isEmpty())
                                    <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
                                @else
                                    <ul class="list-disc pl-space-lg space-y-space-xs">
                                        @foreach ($classPoliciesByScope['f2f_video'] as $policy)
                                            <li class="rte-content text-body-sm text-on-surface">{!! $policy->content !!}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-space-lg py-space-md">
                                @if ($classPoliciesByScope['online']->isEmpty())
                                    <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
                                @else
                                    <ul class="list-disc pl-space-lg space-y-space-xs">
                                        @foreach ($classPoliciesByScope['online'] as $policy)
                                            <li class="rte-content text-body-sm text-on-surface">{!! $policy->content !!}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        @foreach ($classPoliciesByScope['general'] as $policy)
                            <tr class="{{ ! $loop->last ? 'border-b border-outline-variant' : '' }}">
                                <td colspan="2" class="px-space-lg py-space-md">
                                    <ul class="list-disc pl-space-lg">
                                        <li class="rte-content text-body-sm text-on-surface">{!! $policy->content !!}</li>
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Submission & Collection -->
    <div id="readonly-section-submission_and_collection" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Submission & Collection</h3>
        @if ($syllabus->submission_and_collection)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->submission_and_collection !!}</div>
        @else
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Tutorial Activity Plan -->
    <div id="readonly-section-tutorial_activity_plan" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Tutorial Activity Plan</h3>
        @if ($syllabus->tutorial_activity_plan)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->tutorial_activity_plan !!}</div>
        @else
            <p class="text-body-md text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Learning Outcomes -->
    <div id="readonly-section-learning_outcomes" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Learning Outcomes</h3>
        @forelse ($syllabus->learningOutcomes as $outcome)
            <div class="flex items-start gap-space-sm">
                <span class="font-label-sm text-label-sm text-primary flex-shrink-0">{{ $outcome->code }}</span>
                <span class="rte-content text-body-sm text-on-surface">{!! $outcome->description !!}</span>
            </div>
        @empty
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @endforelse
    </div>

    <!-- Evaluation -->
    <div id="readonly-section-evaluation" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
        <h3 class="font-label-lg text-label-lg text-on-surface">Evaluation</h3>
        @forelse ($evaluationsWithTotals as $group)
            <div class="border border-outline-variant rounded-lg p-space-md space-y-space-xs">
                <div class="flex items-center justify-between">
                    <span class="font-label-md text-label-md text-on-surface">{{ $group['evaluation']->class_type }}</span>
                    <span class="text-body-xs text-on-surface-variant">Total: {{ $group['totalWeight'] }}%</span>
                </div>
                @foreach ($group['evaluation']->activities as $activity)
                    <div class="flex items-center justify-between text-body-sm">
                        <span class="text-on-surface">{{ $activity->activity }}</span>
                        <span class="text-on-surface-variant">{{ $activity->weight }}%</span>
                    </div>
                @endforeach
            </div>
        @empty
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @endforelse
    </div>

    <!-- Assessment Rubric -->
    <div id="readonly-section-assessment_rubric" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
        <h3 class="font-label-lg text-label-lg text-on-surface">Assessment Rubric</h3>

        @if ($syllabus->learningOutcomes->isEmpty() || $syllabus->rubricProficiencyLevels->isEmpty())
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @else
            <div class="border border-outline-variant rounded-lg overflow-x-auto">
                <table class="w-full border-collapse min-w-[720px]">
                    <thead>
                        <tr class="border-b border-outline-variant">
                            <th rowspan="2" class="align-bottom px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface border-r border-outline-variant">Learning Outcome</th>
                            <th rowspan="2" class="align-bottom px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface border-r border-outline-variant">Key Indicator</th>
                            <th colspan="{{ $syllabus->rubricProficiencyLevels->count() }}" class="px-space-lg py-space-sm text-center font-label-md text-label-md text-on-surface">Proficiency Level</th>
                        </tr>
                        <tr class="border-b border-outline-variant">
                            @foreach ($syllabus->rubricProficiencyLevels as $level)
                                <th class="px-space-lg py-space-sm text-left font-label-sm text-label-sm text-on-surface">
                                    {{ $level->label }}<br>
                                    <span class="font-body-xs text-body-xs text-on-surface-variant font-normal">({{ $level->score_min }} - {{ $level->score_max }})</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($syllabus->learningOutcomes as $loIndex => $outcome)
                            @forelse ($outcome->rubricKeyIndicators as $kiIndex => $indicator)
                                <tr class="border-b border-outline-variant align-top {{ $loIndex % 2 === 1 ? 'bg-surface-container' : '' }}">
                                    @if ($kiIndex === 0)
                                        <td rowspan="{{ $outcome->rubricKeyIndicators->count() }}" class="px-space-lg py-space-md text-body-sm text-on-surface border-r border-outline-variant">
                                            <strong>{{ $outcome->code }}:</strong> {{ $outcome->description }}
                                        </td>
                                    @endif
                                    <td class="px-space-lg py-space-md text-body-sm text-on-surface border-r border-outline-variant">
                                        {{ $indicator->code }}. {{ $indicator->description }}
                                    </td>
                                    @foreach ($syllabus->rubricProficiencyLevels as $level)
                                        <td class="rte-content px-space-lg py-space-md text-body-sm text-on-surface-variant">
                                            {!! $indicator->cells->firstWhere('rubric_proficiency_level_id', $level->id)?->description ?: '—' !!}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr class="border-b border-outline-variant {{ $loIndex % 2 === 1 ? 'bg-surface-container' : '' }}">
                                    <td class="px-space-lg py-space-md text-body-sm text-on-surface border-r border-outline-variant">
                                        <strong>{{ $outcome->code }}:</strong> {{ $outcome->description }}
                                    </td>
                                    <td colspan="{{ $syllabus->rubricProficiencyLevels->count() + 1 }}" class="px-space-lg py-space-md text-body-sm text-on-surface-variant">
                                        No key indicators yet.
                                    </td>
                                </tr>
                            @endforelse
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Teaching & Learning Strategies -->
    <div id="readonly-section-teaching_learning_strategies" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Teaching & Learning Strategies</h3>
        @if ($syllabus->teaching_learning_strategies)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->teaching_learning_strategies !!}</div>
        @else
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Textbooks -->
    <div id="readonly-section-textbooks" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Textbooks</h3>
        @if ($syllabus->textbooks)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->textbooks !!}</div>
        @else
            <p class="text-body-sm text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Competency Map -->
    <div id="readonly-section-competency_map" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Competency Map</h3>
        @if ($syllabus->competency_map)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->competency_map !!}</div>
        @else
            <p class="text-body-md text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Video Overview -->
    <div id="readonly-section-video_overview" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        <h3 class="font-label-lg text-label-lg text-on-surface">Video Overview</h3>
        @if ($syllabus->video_overview)
            <div class="rte-content text-body-md text-on-surface-variant">{!! $syllabus->video_overview !!}</div>
        @else
            <p class="text-body-md text-on-surface-variant">Not provided yet.</p>
        @endif
    </div>

    <!-- Materials -->
    @if ($syllabus->materials->isNotEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
            <h3 class="font-label-lg text-label-lg text-on-surface">Attached Materials</h3>
            @foreach ($syllabus->materials as $material)
                <div class="flex items-center gap-space-sm text-body-sm">
                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">description</span>
                    <span class="text-on-surface">{{ $material->title }}</span>
                    <span class="text-on-surface-variant text-body-xs">({{ $material->pivot->section }})</span>
                </div>
            @endforeach
        </div>
    @endif
    </div>
</div>
