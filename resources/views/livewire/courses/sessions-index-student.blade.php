@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{
        viewingPayload: null,
        viewerLoading: false,
        pendingSessionId: null,
        showThreadForm: false,
        sessionDeliveryModes: @js($sessions->mapWithKeys(fn ($session) => [(string) $session->id => $session->delivery_mode->value])),
        async selectSession(id) {
            this.pendingSessionId = id;
            this.viewingPayload = null;
            this.viewerLoading = false;
            this.showThreadForm = false;
            await this.$wire.selectSession(id);
            this.pendingSessionId = null;
        },
    }"
    x-on:thread-created.window="showThreadForm = false"
>
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    @if ($sessions->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">calendar_month</span>
            <p class="text-body-md text-on-surface-variant">No sessions yet.</p>
        </div>
    @else
        <!-- Session Tabs -->
        <div class="flex gap-space-xs overflow-x-auto pb-space-xs border-b border-outline-variant">
            @foreach ($sessions as $session)
                <button
                    type="button"
                    @click="selectSession('{{ $session->id }}')"
                    wire:key="session-tab-{{ $session->id }}"
                    class="flex-shrink-0 px-space-md py-space-sm rounded-t-lg font-label-sm text-label-sm whitespace-nowrap border-b-2 transition-colors"
                    :class="(pendingSessionId ? pendingSessionId === '{{ $session->id }}' : {{ $activeSession && $activeSession->id === $session->id ? 'true' : 'false' }}) ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container text-on-surface-variant border-transparent hover:bg-surface-container/70'"
                >
                    {{ $session->title }}
                </button>
            @endforeach
        </div>

        <!-- Skeleton loading while switching sessions -->
        <div wire:loading wire:target="selectSession" class="w-full space-y-space-lg animate-pulse">
            <!-- Session Detail skeleton -->
            <div class="w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
                <div class="flex items-start justify-between gap-space-md">
                    <div class="h-6 bg-surface-container rounded w-1/3"></div>
                    <div class="flex flex-col items-stretch gap-space-sm flex-shrink-0">
                        <div class="h-9 w-40 bg-surface-container rounded-lg"></div>
                        <div class="h-9 w-40 bg-surface-container rounded-lg" x-show="sessionDeliveryModes[pendingSessionId] === 'virtual_class'"></div>
                    </div>
                </div>

                <div class="space-y-space-sm">
                    <div class="h-3 bg-surface-container rounded w-28"></div>
                    <div class="flex items-start gap-space-sm">
                        <div class="w-1.5 h-1.5 rounded-full bg-surface-container mt-2 flex-shrink-0"></div>
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                    </div>
                </div>

                <div class="space-y-space-sm">
                    <div class="h-3 bg-surface-container rounded w-24"></div>
                    <div class="flex items-start gap-space-sm">
                        <div class="w-1.5 h-1.5 rounded-full bg-surface-container mt-2 flex-shrink-0"></div>
                        <div class="h-3 bg-surface-container rounded w-3/4"></div>
                    </div>
                    <div class="flex items-start gap-space-sm">
                        <div class="w-1.5 h-1.5 rounded-full bg-surface-container mt-2 flex-shrink-0"></div>
                        <div class="h-3 bg-surface-container rounded w-2/3"></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-space-md pt-space-md border-t border-outline-variant">
                    <div class="space-y-space-xs">
                        <div class="h-2 bg-surface-container rounded w-10"></div>
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                    </div>
                    <div class="space-y-space-xs">
                        <div class="h-2 bg-surface-container rounded w-8"></div>
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                    </div>
                    <div class="space-y-space-xs">
                        <div class="h-2 bg-surface-container rounded w-20"></div>
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                    </div>
                </div>
            </div>

            <!-- Learning Progress skeleton -->
            <div class="w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
                <div class="space-y-space-md">
                    <div class="flex items-center gap-space-xs">
                        <div class="h-4 bg-surface-container rounded w-32"></div>
                        <div class="h-4 w-4 bg-surface-container rounded"></div>
                        <div class="ml-auto h-4 bg-surface-container rounded w-10"></div>
                    </div>

                    <div class="w-full h-2 bg-surface-container rounded-full"></div>

                    <div class="flex flex-wrap gap-space-sm">
                        <div class="h-7 bg-surface-container rounded-full w-36"></div>
                        <div class="h-7 bg-surface-container rounded-full w-28"></div>
                        <div class="h-7 bg-surface-container rounded-full w-24"></div>
                        <div class="h-7 bg-surface-container rounded-full w-20"></div>
                    </div>
                </div>

                <div class="pt-space-md border-t border-outline-variant flex items-center gap-space-md">
                    <div class="h-3 bg-surface-container rounded w-1/4"></div>
                    <div class="w-9 h-9 rounded-full bg-surface-container flex-shrink-0 ml-auto"></div>
                </div>

                <div class="flex flex-col items-center gap-space-lg py-space-lg">
                    <div class="w-40 h-40 rounded-full bg-surface-container"></div>
                    <div class="h-12 w-48 bg-surface-container rounded-lg"></div>
                </div>
            </div>
        </div>

        @if ($activeSession)
            <!-- Session Detail -->
            <div wire:loading.remove wire:target="selectSession" class="relative bg-surface border border-outline-variant rounded-lg p-space-lg">
                <div class="flex flex-col items-stretch gap-space-sm absolute top-space-lg right-space-lg">
                    <a
                        href="#progress-{{ $activeSession->id }}"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity inline-flex items-center justify-center gap-space-xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        Go to Resources
                    </a>

                    @if ($showVideoConferences)
                        @foreach ($activeSession->videoConferences as $videoConference)
                            @if ($videoConferenceWindowOpen)
                                <a
                                    wire:key="video-conference-{{ $videoConference->id }}"
                                    wire:click="markVideoConferenceOpened('{{ $videoConference->id }}')"
                                    href="{{ $videoConference->meeting_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-space-lg py-space-sm border border-outline text-on-surface rounded-lg font-label-sm text-label-sm hover:bg-surface-container transition-colors inline-flex items-center justify-center gap-space-xs"
                                >
                                    @if ($openedVideoConferenceIds->contains($videoConference->id))
                                        <span class="material-symbols-outlined text-success text-[18px]" data-weight="fill">check_circle</span>
                                    @else
                                        <span class="material-symbols-outlined text-[18px]">videocam</span>
                                    @endif
                                    {{ $videoConference->title ?: 'Video Conference' }}
                                </a>
                            @else
                                <span
                                    wire:key="video-conference-{{ $videoConference->id }}"
                                    title="Available only during the session's scheduled dates"
                                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface-variant rounded-lg font-label-sm text-label-sm inline-flex items-center justify-center gap-space-xs opacity-50 cursor-not-allowed"
                                >
                                    <span class="material-symbols-outlined text-[18px]">videocam_off</span>
                                    {{ $videoConference->title ?: 'Video Conference' }}
                                </span>
                            @endif
                        @endforeach
                        @if (! $videoConferenceWindowOpen)
                            <p class="text-body-xs text-on-surface-variant text-right">Available only during the session's scheduled dates.</p>
                        @endif
                    @endif
                </div>

                <h1 class="font-headline-sm text-headline-sm text-on-surface pr-48">{{ $activeSession->title }}</h1>

                @if ($activeSession->learning_outcome)
                    <div class="mt-space-sm">
                        <h3 class="font-label-md text-label-md text-on-surface mb-space-sm">Learning Outcome</h3>
                        <ul class="space-y-space-xs">
                            <li class="flex items-start gap-space-sm text-body-sm text-primary">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                <span>{{ $activeSession->learning_outcome }}</span>
                            </li>
                        </ul>
                    </div>
                @endif

                @if ($activeSession->subtopics->isNotEmpty())
                    <div class="mt-space-lg">
                        <h3 class="font-label-md text-label-md text-on-surface mb-space-sm">Sub Topic</h3>
                        <ul class="space-y-space-xs">
                            @foreach ($activeSession->subtopics as $subtopic)
                                <li wire:key="subtopic-{{ $subtopic->id }}" class="flex items-start gap-space-sm text-body-sm text-primary">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 flex-shrink-0"></span>
                                    <span>{{ $subtopic->subtopic }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-space-md mt-space-lg pt-space-md border-t border-outline-variant">
                    <div>
                        <p class="text-body-xs text-on-surface-variant uppercase mb-1">Start</p>
                        <p class="text-body-sm text-on-surface">{{ $activeSession->date_start->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-body-xs text-on-surface-variant uppercase mb-1">End</p>
                        <p class="text-body-sm text-on-surface">{{ $activeSession->date_end->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-body-xs text-on-surface-variant uppercase mb-1">Delivery Mode</p>
                        <p class="text-body-sm text-on-surface">{{ str($activeSession->delivery_mode->value)->replace('_', ' ')->title() }}</p>
                    </div>
                </div>
            </div>

            <!-- Learning Progress -->
            <div
                id="progress-{{ $activeSession->id }}"
                wire:loading.remove
                wire:target="selectSession"
                wire:key="progress-{{ $activeSession->id }}"
                x-data="{ activeChipKey: @js($activeChipKey), materialPayloads: @js($materialPayloads) }"
                class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg"
            >
                <div class="space-y-space-md">
                    <div class="flex items-center gap-space-xs">
                        <h3 class="font-label-md text-label-md text-on-surface">Learning Progress</h3>
                        <span class="relative group">
                            <span class="material-symbols-outlined text-on-surface-variant text-[16px] bg-surface-container rounded p-0.5 cursor-help">info</span>
                            <span class="pointer-events-none absolute left-0 bottom-full mb-space-xs hidden group-hover:block w-56 p-space-sm bg-on-surface text-surface text-body-xs rounded-lg shadow-lg z-10">
                                Tracks your progress across this session's learning material, assessment, and forum.
                            </span>
                        </span>
                        <span class="ml-auto text-body-sm text-on-surface font-semibold">{{ $progressPercent }}%</span>
                    </div>

                    <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full bg-success transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                    </div>

                    <div class="flex flex-wrap gap-space-sm">
                        @foreach ($chips as $chip)
                            <button
                                type="button"
                                @click="activeChipKey = '{{ $chip['key'] }}'; viewingPayload = null; viewerLoading = false"
                                wire:click="selectChip('{{ $chip['key'] }}')"
                                wire:key="chip-{{ $chip['key'] }}"
                                class="inline-flex items-center h-7 gap-space-xs {{ $chip['completed'] ? 'pl-space-xs' : 'pl-space-md' }} pr-space-md rounded-full border font-label-sm text-label-sm transition-colors"
                                :class="activeChipKey === '{{ $chip['key'] }}' ? 'border-primary bg-primary/5 text-on-surface' : 'border-outline-variant text-on-surface hover:bg-surface-container/50'"
                            >
                                @if ($chip['completed'])
                                    <span class="flex items-center justify-center w-5 h-5 rounded bg-success text-white">
                                        <span class="material-symbols-outlined text-[14px]" data-weight="fill">check</span>
                                    </span>
                                @endif
                                {{ $chip['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($activeSession->materials->isEmpty() && $activeSession->assessments->isEmpty() && $activeSession->forums->isEmpty())
                    <p class="pt-space-md border-t border-outline-variant text-body-sm text-on-surface-variant text-center">
                        Nothing here yet.
                    </p>
                @else
                    <div class="pt-space-md border-t border-outline-variant">
                        @foreach ($activeSession->materials as $material)
                            <div x-show="activeChipKey === 'material:{{ $material->id }}'" x-cloak class="flex items-center gap-space-md">
                                <div class="min-w-0 flex-1">
                                    <p class="text-body-xs text-on-surface-variant flex items-center gap-space-xs">
                                        <span class="material-symbols-outlined text-[14px]">description</span>
                                        {{ $material->type->value }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="markMaterialCompleted('{{ $material->id }}')"
                                    @click="viewerLoading = true; viewingPayload = materialPayloads['{{ $material->id }}']; setTimeout(() => viewerLoading = false, 500)"
                                    class="w-9 h-9 flex items-center justify-center rounded-full border border-outline-variant hover:bg-surface-container transition-colors text-on-surface-variant flex-shrink-0"
                                    title="Download material"
                                >
                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                </button>
                            </div>
                        @endforeach

                        <div x-show="activeChipKey === 'assessment'" x-cloak class="space-y-space-md">
                            <!-- Assessment skeleton loading (opening the tab) -->
                            <div wire:loading wire:target="selectChip('assessment')" class="w-full space-y-space-md animate-pulse">
                                @for ($i = 0; $i < 2; $i++)
                                    <div class="border border-outline-variant rounded-lg overflow-hidden">
                                        <div class="flex items-center justify-between px-space-md py-space-sm bg-surface-container/50">
                                            <div class="h-4 bg-surface-container rounded w-40"></div>
                                            <div class="h-3 bg-surface-container rounded w-20"></div>
                                        </div>
                                        <div class="border-t border-outline-variant divide-y divide-outline-variant">
                                            @for ($j = 0; $j < 2; $j++)
                                                <div class="flex items-center gap-space-lg px-space-md py-space-sm">
                                                    <div class="h-3 bg-surface-container rounded w-32"></div>
                                                    <div class="h-3 bg-surface-container rounded w-20"></div>
                                                    <div class="h-3 bg-surface-container rounded w-24"></div>
                                                    <div class="h-3 bg-surface-container rounded w-24"></div>
                                                    <div class="h-5 bg-surface-container rounded-full w-16"></div>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div wire:loading.remove wire:target="selectChip('assessment')" class="space-y-space-md">
                            @if (empty($assessmentGroups))
                                <p class="text-body-xs text-on-surface-variant flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[14px]">assignment</span>
                                    No assessment yet.
                                </p>
                            @else
                                @foreach ($assessmentGroups as $group)
                                    <div x-data="{ open: true }" class="border border-outline-variant rounded-lg overflow-hidden">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="w-full flex items-center justify-between px-space-md py-space-sm bg-surface-container/50 hover:bg-surface-container transition"
                                        >
                                            <div class="flex items-center gap-space-sm">
                                                <span class="material-symbols-outlined text-on-surface-variant text-[18px] transition-transform" :class="open ? 'rotate-90' : ''">
                                                    chevron_right
                                                </span>
                                                <span class="font-label-sm text-label-sm text-on-surface uppercase">
                                                    {{ \App\Support\AssessmentTypeLabel::forType($group['type']) }}: {{ rtrim(rtrim(number_format($group['totalWeight'], 2), '0'), '.') }}%
                                                </span>
                                            </div>
                                            <span class="text-body-xs text-on-surface-variant">
                                                {{ $group['rows']->count() }} assessment{{ $group['rows']->count() !== 1 ? 's' : '' }}
                                            </span>
                                        </button>

                                        <div x-show="open" x-cloak class="overflow-x-auto border-t border-outline-variant">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="border-b border-outline-variant bg-surface-container/30">
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Title</th>
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Assigned to</th>
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Start Date</th>
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Due Date</th>
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Status</th>
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Attempt</th>
                                                        <th class="px-space-md py-space-sm text-left font-label-sm text-label-sm text-on-surface-variant">Score</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-outline-variant">
                                                    @foreach ($group['rows'] as $row)
                                                        <tr wire:key="session-assessment-{{ $row['assessment']->id }}" class="hover:bg-surface-container/30 transition">
                                                            <td class="px-space-md py-space-sm">
                                                                @if ($row['route'])
                                                                    <a href="{{ $row['route'] }}" class="text-primary hover:underline font-label-sm text-label-sm">
                                                                        {{ $row['assessment']->title }}
                                                                    </a>
                                                                @else
                                                                    <span class="text-on-surface-variant opacity-60 font-label-sm text-label-sm">
                                                                        {{ $row['assessment']->title }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-space-md py-space-sm text-body-sm text-on-surface">
                                                                <span class="inline-flex items-center gap-space-xs">
                                                                    <span class="material-symbols-outlined text-[16px]">
                                                                        {{ $row['assessment']->assigned_to->value === 'individual' ? 'person' : 'groups' }}
                                                                    </span>
                                                                    {{ str($row['assessment']->assigned_to->value)->title() }}
                                                                </span>
                                                            </td>
                                                            <td class="px-space-md py-space-sm text-body-sm text-on-surface">
                                                                @if ($row['assessment']->start_date)
                                                                    {{ $row['assessment']->start_date->format('M j, Y, H:i') }}
                                                                @else
                                                                    <span class="text-on-surface-variant">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-space-md py-space-sm">
                                                                <div class="flex items-center gap-space-xs">
                                                                    @if ($row['assessment']->end_date)
                                                                        <span class="text-body-sm text-on-surface">{{ $row['assessment']->end_date->format('M j, Y, H:i') }}</span>
                                                                        @if ($row['isExpired'])
                                                                            <span class="inline-flex items-center px-space-xs py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">
                                                                                Expired
                                                                            </span>
                                                                        @endif
                                                                    @else
                                                                        <span class="text-on-surface-variant">—</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td class="px-space-md py-space-sm">
                                                                <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm {{ $row['statusConfig']['bg'] }} {{ $row['statusConfig']['text'] }}">
                                                                    <span class="material-symbols-outlined text-[16px]">{{ $row['statusConfig']['icon'] }}</span>
                                                                    {{ str($row['status'])->replace('_', ' ')->title() }}
                                                                </span>
                                                            </td>
                                                            <td class="px-space-md py-space-sm text-body-sm text-on-surface">
                                                                @if ($row['route'])
                                                                    {{ $row['attemptCount'] }} of {{ $row['attemptLimit'] }}
                                                                @else
                                                                    <span class="text-on-surface-variant">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-space-md py-space-sm text-body-sm text-on-surface font-label-sm">
                                                                @if ($row['score'] !== null)
                                                                    {{ number_format($row['score'], 1) }}
                                                                @else
                                                                    <span class="text-on-surface-variant">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            </div>
                        </div>

                        <div x-show="activeChipKey === 'forum'" x-cloak class="space-y-space-md">
                            <!-- Forum skeleton loading (opening the tab) -->
                            <div wire:loading wire:target="selectChip('forum')" class="w-full space-y-space-md animate-pulse">
                                <div class="w-full flex flex-wrap gap-space-xl">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div class="space-y-space-xs">
                                            <div class="h-3 bg-surface-container rounded w-10"></div>
                                            <div class="h-4 bg-surface-container rounded w-32"></div>
                                        </div>
                                    @endfor
                                </div>

                                <div class="h-10 w-44 bg-surface-container rounded-lg"></div>

                                <div class="w-full flex flex-wrap items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant">
                                    <div class="h-4 bg-surface-container rounded w-20"></div>
                                    <div class="h-9 w-40 bg-surface-container rounded-lg"></div>
                                </div>

                                <div class="w-full border border-outline-variant rounded-lg divide-y divide-outline-variant overflow-hidden">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="w-full p-space-md flex items-start gap-space-md">
                                            <div class="w-10 h-10 rounded-full bg-surface-container flex-shrink-0"></div>
                                            <div class="min-w-0 flex-1 space-y-space-xs">
                                                <div class="h-3 bg-surface-container rounded w-1/4"></div>
                                                <div class="h-3 bg-surface-container rounded w-1/3"></div>
                                                <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                            </div>
                                            <div class="h-4 w-8 bg-surface-container rounded flex-shrink-0"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <div wire:loading.remove wire:target="selectChip('forum')" class="w-full space-y-space-md">
                            @if ($activeSession->forums->isNotEmpty())
                                <div class="flex flex-wrap gap-space-xl">
                                    <div>
                                        <p class="text-body-xs text-on-surface-variant">Start</p>
                                        <p class="text-body-sm text-on-surface">{{ $activeSession->date_start->format('d M Y, H:i') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-body-xs text-on-surface-variant">End</p>
                                        <p class="text-body-sm text-on-surface">{{ $activeSession->date_end->format('d M Y, H:i') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-body-xs text-on-surface-variant">Total Post</p>
                                        <p class="text-body-sm text-on-surface">{{ $forumTotalPosts }}</p>
                                    </div>
                                    <div>
                                        <p class="text-body-xs text-on-surface-variant">My Post</p>
                                        <p class="text-body-sm text-on-surface inline-flex items-center gap-1">
                                            {{ min($forumMyPostsCount, $forumRequiredPosts) }} of {{ $forumRequiredPosts }}
                                            @if ($forumMyPostsCount >= $forumRequiredPosts)
                                                <span class="material-symbols-outlined text-[16px] text-green-600" data-weight="fill">check_circle</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if ($canCreateForumThread)
                                    <button
                                        @click="showThreadForm = true"
                                        x-show="! showThreadForm"
                                        type="button"
                                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
                                    >
                                        Create New Thread
                                    </button>

                                    <div x-show="showThreadForm" x-cloak class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                                        <div>
                                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Title</label>
                                            <input
                                                type="text"
                                                wire:model="newThreadTitle"
                                                class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                            />
                                            @error('newThreadTitle') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                                            <x-rich-text-editor id="new-thread-session" wire-model="newThreadDescription" :value="$newThreadDescription" :disabled="! $forumWindowOpen" />
                                            @error('newThreadDescription') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                        </div>

                                        @if (! $forumWindowOpen)
                                            <p class="text-body-xs text-on-surface-variant">Posting is only available during the session's scheduled dates.</p>
                                        @endif

                                        <div class="flex gap-space-md">
                                            <button
                                                @click="showThreadForm = false"
                                                type="button"
                                                class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                wire:click="createThread"
                                                wire:loading.attr="disabled"
                                                wire:target="createThread"
                                                type="button"
                                                @disabled(! $forumWindowOpen)
                                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                                            >
                                                <span wire:loading.remove wire:target="createThread">Post Thread</span>
                                                <span wire:loading wire:target="createThread">Posting…</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if ($forumPagination)
                                    <div class="flex flex-wrap items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant">
                                        <p class="text-body-sm text-on-surface-variant">{{ $forumPagination['total'] }} Result{{ $forumPagination['total'] !== 1 ? 's' : '' }}</p>

                                        <div class="flex items-center gap-space-lg">
                                            <label class="flex items-center gap-space-sm">
                                                <span class="text-body-sm text-on-surface-variant">Show:</span>
                                                <select wire:model.live="forumPerPage" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                    <option value="5">5</option>
                                                    <option value="10">10</option>
                                                    <option value="25">25</option>
                                                </select>
                                            </label>

                                            @if ($forumPagination['lastPage'] > 1)
                                                <div class="flex items-center gap-space-xs">
                                                    <button wire:click="gotoForumPage(1)" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled($forumPagination['onFirstPage'])>«</button>
                                                    <button wire:click="gotoForumPage({{ $forumPagination['currentPage'] - 1 }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled($forumPagination['onFirstPage'])>‹</button>
                                                    <button wire:click="gotoForumPage({{ $forumPagination['currentPage'] + 1 }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled(! $forumPagination['hasMorePages'])>›</button>
                                                    <button wire:click="gotoForumPage({{ $forumPagination['lastPage'] }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled(! $forumPagination['hasMorePages'])>»</button>
                                                </div>

                                                <label class="flex items-center gap-space-sm">
                                                    <span class="text-body-sm text-on-surface-variant">Page:</span>
                                                    <select wire:model.live="forumPage" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                        @for ($i = 1; $i <= $forumPagination['lastPage']; $i++)
                                                            <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </label>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Thread list skeleton while changing per-page/page -->
                                <div wire:loading wire:target="forumPerPage, gotoForumPage, forumPage" class="w-full border border-outline-variant rounded-lg divide-y divide-outline-variant overflow-hidden animate-pulse">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="w-full p-space-md flex items-start gap-space-md">
                                            <div class="w-10 h-10 rounded-full bg-surface-container flex-shrink-0"></div>
                                            <div class="min-w-0 flex-1 space-y-space-xs">
                                                <div class="h-3 bg-surface-container rounded w-1/4"></div>
                                                <div class="h-3 bg-surface-container rounded w-1/3"></div>
                                                <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                            </div>
                                            <div class="h-4 w-8 bg-surface-container rounded flex-shrink-0"></div>
                                        </div>
                                    @endfor
                                </div>

                                <div wire:loading.remove wire:target="forumPerPage, gotoForumPage, forumPage">
                                @if (! empty($forumThreadPreviews))
                                    <div class="border border-outline-variant rounded-lg divide-y divide-outline-variant overflow-hidden">
                                        @foreach ($forumThreadPreviews as $thread)
                                                <a
                                                    href="{{ route('forum.thread.show', [$course, $thread['id']]) }}"
                                                    wire:navigate
                                                    wire:key="forum-preview-{{ $thread['id'] }}"
                                                    class="flex items-start gap-space-md p-space-md hover:bg-surface-container/50 transition-colors"
                                                >
                                                    @if ($thread['userAvatarUrl'])
                                                        <img src="{{ $thread['userAvatarUrl'] }}" alt="{{ $thread['userName'] }}" class="w-10 h-10 rounded-full object-cover border border-outline-variant flex-shrink-0">
                                                    @else
                                                        <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-label-md text-label-md flex-shrink-0">
                                                            {{ $thread['userInitial'] }}
                                                        </div>
                                                    @endif

                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-body-sm text-on-surface">
                                                            <span class="font-medium">{{ $thread['userName'] }}</span>
                                                            @if ($thread['roleLabel'])
                                                                <span class="text-on-surface-variant">&middot;</span>
                                                                <span class="text-primary">{{ $thread['roleLabel'] }}</span>
                                                            @endif
                                                        </p>
                                                        <p class="text-body-xs text-on-surface-variant">{{ $thread['createdAtLabel'] }}</p>
                                                        <h3 class="font-body-md text-body-md text-on-surface mt-1">{{ $thread['title'] }}</h3>
                                                    </div>

                                                    <span class="inline-flex items-center gap-1 text-body-sm text-on-surface-variant flex-shrink-0">
                                                        <span class="material-symbols-outlined text-[18px]">chat_bubble_outline</span>
                                                        {{ $thread['commentsCount'] }}
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-body-sm text-on-surface-variant flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[14px]">forum</span>
                                    No forum yet.
                                </p>
                            @endif
                        </div>

                    </div>
                @endif

                <!-- Idle state: illustration + Start Learning -->
                <div class="flex flex-col items-center text-center gap-space-lg py-space-lg" x-show="!viewingPayload && !viewerLoading && activeChipKey !== 'forum' && activeChipKey !== 'assessment'">
                    <div class="w-40 h-40 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[72px]">school</span>
                    </div>

                    <button
                        type="button"
                        x-show="activeChipKey.startsWith('material:')"
                        @click="
                            const materialId = activeChipKey.split(':')[1];
                            $wire.markMaterialCompleted(materialId);
                            viewerLoading = true;
                            viewingPayload = materialPayloads[materialId];
                            setTimeout(() => viewerLoading = false, 500);
                        "
                        class="px-space-xl py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        Start Learning
                    </button>
                </div>

                <!-- Skeleton loading -->
                <div x-show="viewerLoading" x-cloak class="w-full space-y-space-md animate-pulse">
                    <div class="flex items-center justify-between gap-space-md">
                        <div class="h-4 bg-surface-container rounded flex-1"></div>
                        <div class="h-4 w-4 bg-surface-container rounded flex-shrink-0"></div>
                    </div>
                    <div class="w-full aspect-video bg-surface-container rounded-lg"></div>
                </div>

                <!-- Inline material viewer -->
                <div x-show="viewingPayload && !viewerLoading" x-cloak>
                    <div
                        class="bg-surface-container rounded-lg"
                        :class="viewingPayload?.type === 'Markdown' ? 'max-h-[60vh] overflow-y-auto' : 'aspect-video overflow-hidden'"
                    >
                        <template x-if="viewingPayload?.type === 'Video'">
                            <video :src="viewingPayload.url" width="100%" height="100%" controls class="w-full h-full">
                                Your browser does not support the video tag.
                            </video>
                        </template>

                        <template x-if="viewingPayload?.type === 'PDF'">
                            <embed :src="viewingPayload.url" type="application/pdf" width="100%" height="100%" class="rounded" />
                        </template>

                        <template x-if="viewingPayload?.type === 'Audio'">
                            <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                                <span class="text-5xl">🎵</span>
                                <p class="text-body-md text-on-surface" x-text="viewingPayload.title"></p>
                                <audio :src="viewingPayload.url" controls class="w-full">
                                    Your browser does not support the audio element.
                                </audio>
                            </div>
                        </template>

                        <template x-if="viewingPayload?.type === 'Image'">
                            <div class="w-full h-full flex items-center justify-center overflow-auto">
                                <img :src="viewingPayload.url" :alt="viewingPayload.title" class="max-w-full max-h-full" />
                            </div>
                        </template>

                        <template x-if="viewingPayload?.type === 'Interactive'">
                            <iframe
                                :src="viewingPayload.url"
                                class="w-full h-full rounded border-0"
                                sandbox="allow-scripts allow-same-origin allow-forms"
                                :title="viewingPayload.title"
                            ></iframe>
                        </template>

                        <template x-if="viewingPayload?.type === 'Presentation'">
                            <iframe
                                :src="'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(viewingPayload.url)"
                                width="100%"
                                height="100%"
                                frameborder="0"
                                class="rounded"
                            ></iframe>
                        </template>

                        <template x-if="viewingPayload?.type === 'Document'">
                            <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                                <span class="text-5xl">📝</span>
                                <p class="text-body-md text-on-surface" x-text="viewingPayload.title"></p>
                                <a
                                    :href="viewingPayload.url"
                                    download
                                    class="px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition"
                                >
                                    Download Document
                                </a>
                            </div>
                        </template>

                        <template x-if="viewingPayload?.type === 'Markdown'">
                            <div
                                x-data="{ html: null, error: null }"
                                x-init="
                                    fetch(viewingPayload.url)
                                        .then(response => {
                                            if (! response.ok) throw new Error('HTTP ' + response.status);
                                            return response.text();
                                        })
                                        .then(markdown => { html = window.renderMarkdown(markdown); })
                                        .catch(err => { error = err.message; });
                                "
                                class="w-full p-space-lg"
                            >
                                <div class="w-full text-on-surface">
                                    <template x-if="! html && ! error">
                                        <p class="text-center text-on-surface-variant">Loading...</p>
                                    </template>
                                    <template x-if="error">
                                        <p class="text-red-600 font-medium" x-text="'Error loading markdown: ' + error"></p>
                                    </template>
                                    <div x-show="html" x-html="html"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@push('scripts')
    @include('partials.markdown-renderer-script')
@endpush
