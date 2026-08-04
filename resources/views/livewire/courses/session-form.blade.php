@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="max-w-2xl mx-auto">
        <!-- Breadcrumb -->
        <div class="mb-space-lg">
            <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
                <a href="{{ route('courses.index') }}" class="hover:text-on-surface transition">Courses</a>
                <span>/</span>
                <a href="{{ route('sessions.index', $course) }}" class="hover:text-on-surface transition">{{ $course->title }}</a>
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
                href="{{ route('sessions.index', $course) }}"
                class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors flex-shrink-0"
            >
                Back to Sessions
            </a>
        </div>

        <form wire:submit="save" class="space-y-space-lg">
            <!-- Title -->
            <div>
                <label for="title" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Session Title <span class="text-error">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    placeholder="e.g., Session 1: Introduction"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('title') border-error @enderror"
                />
                @error('title')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Learning Outcome -->
            <div>
                <label for="learningOutcome" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Learning Outcome
                </label>
                <textarea
                    id="learningOutcome"
                    wire:model="learningOutcome"
                    placeholder="What will students be able to do after this session?"
                    rows="3"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('learningOutcome') border-error @enderror"
                ></textarea>
                @error('learningOutcome')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label for="dateStart" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                        Start Date <span class="text-error">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="dateStart"
                        wire:model="dateStart"
                        class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('dateStart') border-error @enderror"
                    />
                    @error('dateStart')
                        <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="dateEnd" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                        End Date <span class="text-error">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="dateEnd"
                        wire:model="dateEnd"
                        class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('dateEnd') border-error @enderror"
                    />
                    @error('dateEnd')
                        <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Delivery Mode -->
            <div>
                <label for="deliveryMode" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Delivery Mode <span class="text-error">*</span>
                </label>
                <select
                    id="deliveryMode"
                    wire:model="deliveryMode"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 capitalize"
                >
                    @foreach ($deliveryModes as $mode)
                        <option value="{{ $mode->value }}" class="capitalize">{{ ucfirst($mode->value) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subtopics -->
            <div>
                <label class="block text-label-md text-on-surface mb-space-sm font-label-md">Subtopics</label>
                <div class="space-y-space-sm">
                    @foreach ($subtopics as $index => $subtopic)
                        <div class="flex gap-space-sm" wire:key="subtopic-{{ $index }}">
                            <input
                                type="text"
                                wire:model="subtopics.{{ $index }}"
                                placeholder="Subtopic"
                                class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                            <button
                                type="button"
                                wire:click="removeSubtopic({{ $index }})"
                                class="p-2 hover:bg-surface-container rounded transition text-error"
                            >
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        @error("subtopics.{$index}")
                            <p class="text-body-sm text-error">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
                <button
                    type="button"
                    wire:click="addSubtopic"
                    class="mt-space-sm text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Subtopic
                </button>
            </div>

            <!-- Learning Material -->
            <div>
                <label class="block text-label-md text-on-surface mb-space-sm font-label-md">Learning Material</label>
                @if ($mediaItems->isEmpty())
                    <p class="text-body-sm text-on-surface-variant">No media library items available yet.</p>
                @else
                    <div class="border border-outline rounded-lg divide-y divide-outline-variant max-h-64 overflow-y-auto">
                        @foreach ($mediaItems as $item)
                            <label class="flex items-center gap-space-md p-space-md cursor-pointer hover:bg-surface-container/50">
                                <input
                                    type="checkbox"
                                    wire:model="selectedMaterialIds"
                                    value="{{ $item->id }}"
                                    class="rounded"
                                />
                                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">description</span>
                                <span class="text-body-sm text-on-surface flex-1">{{ $item->title }}</span>
                                <span class="text-body-xs text-on-surface-variant">{{ $item->type }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Video Conferences -->
            <div>
                <label class="block text-label-md text-on-surface mb-space-sm font-label-md">Video Conferences</label>
                <div class="space-y-space-md">
                    @foreach ($videoConferences as $index => $videoConference)
                        <div wire:key="video-conference-{{ $index }}" class="p-space-lg border border-outline-variant rounded-lg space-y-space-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-label-sm text-label-sm text-on-surface">Meeting {{ $index + 1 }}</span>
                                <button type="button" wire:click="removeVideoConference({{ $index }})" class="p-1 hover:bg-surface-container rounded transition text-error">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                            <input
                                type="text"
                                wire:model="videoConferences.{{ $index }}.title"
                                placeholder="Meeting title (e.g., Main Meeting)"
                                class="w-full px-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                            <div class="grid grid-cols-2 gap-space-sm">
                                <input
                                    type="datetime-local"
                                    wire:model="videoConferences.{{ $index }}.scheduled_start_at"
                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                                />
                                <input
                                    type="datetime-local"
                                    wire:model="videoConferences.{{ $index }}.scheduled_end_at"
                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                                />
                            </div>
                            <input
                                type="url"
                                wire:model="videoConferences.{{ $index }}.meeting_url"
                                placeholder="Meeting URL"
                                class="w-full px-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                            <input
                                type="number"
                                min="0"
                                wire:model="videoConferences.{{ $index }}.required_duration_minutes"
                                placeholder="Required duration (minutes) for attendance"
                                class="w-full px-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                        </div>
                    @endforeach
                </div>
                <button
                    type="button"
                    wire:click="addVideoConference"
                    class="mt-space-sm text-primary font-medium text-body-sm hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Video Conference
                </button>
            </div>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ route('sessions.index', $course) }}"
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
                    <span wire:loading.remove wire:target="save">{{ $session ? 'Update Session' : 'Create Session' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
