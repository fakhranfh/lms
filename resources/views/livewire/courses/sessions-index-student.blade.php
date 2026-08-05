@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{
        viewingPayload: null,
        viewerLoading: false,
        pendingSessionId: null,
        sessionDeliveryModes: @js($sessions->mapWithKeys(fn ($session) => [(string) $session->id => $session->delivery_mode->value])),
        async selectSession(id) {
            this.pendingSessionId = id;
            this.viewingPayload = null;
            this.viewerLoading = false;
            await this.$wire.selectSession(id);
            this.pendingSessionId = null;
        },
    }"
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
                        @endforeach
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
                            <span class="pointer-events-none absolute left-1/2 -translate-x-1/2 bottom-full mb-space-xs hidden group-hover:block w-56 p-space-sm bg-on-surface text-surface text-body-xs rounded-lg shadow-lg z-10">
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

                        <div x-show="activeChipKey === 'assessment'" x-cloak class="flex items-center gap-space-md">
                            <div class="min-w-0 flex-1">
                                <p class="text-body-xs text-on-surface-variant flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[14px]">assignment</span>
                                    @if ($activeSession->assessments->isNotEmpty())
                                        {{ $activeSession->assessments->first()->type->value }} &middot; {{ $activeSession->assessments->first()->status->value }}
                                    @else
                                        No assessment yet.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div x-show="activeChipKey === 'forum'" x-cloak class="flex items-center gap-space-md">
                            <div class="min-w-0 flex-1">
                                <p class="text-body-xs text-on-surface-variant flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[14px]">forum</span>
                                    @if ($activeSession->forums->isNotEmpty())
                                        Forum
                                    @else
                                        No forum yet.
                                    @endif
                                </p>
                            </div>
                        </div>

                    </div>
                @endif

                <!-- Idle state: illustration + Start Learning -->
                <div class="flex flex-col items-center text-center gap-space-lg py-space-lg" x-show="!viewingPayload && !viewerLoading">
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
