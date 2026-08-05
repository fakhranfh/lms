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
                    wire:model.live="deliveryMode"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                >
                    @foreach ($deliveryModes as $mode)
                        <option value="{{ $mode->value }}">{{ str($mode->value)->replace('_', ' ')->title() }}</option>
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
            <div
                x-data="materialPicker({
                    initialSelected: @js($selectedMediaItems->map(fn ($item) => ['id' => (string) $item->id, 'title' => $item->title, 'type' => $item->type->value])->values()),
                })"
            >
                <label class="block text-label-md text-on-surface mb-space-sm font-label-md">Learning Material</label>

                <button
                    type="button"
                    @click="open = true"
                    class="w-full flex items-center justify-center gap-space-sm px-space-lg py-space-md border border-dashed border-outline rounded-lg text-body-sm text-primary font-medium hover:bg-surface-container/50 transition-colors"
                >
                    <span class="material-symbols-outlined text-[18px]">perm_media</span>
                    Choose Material from Media Library
                </button>

                <!-- Selected materials -->
                <div class="mt-space-sm space-y-space-xs" x-show="selectedItems.length > 0">
                    <template x-for="item in selectedItems" :key="item.id">
                        <div class="flex items-center gap-space-md p-space-sm border border-outline-variant rounded-lg">
                            <span class="material-symbols-outlined text-on-surface-variant text-[18px]">description</span>
                            <span class="text-body-sm text-on-surface flex-1" x-text="item.title"></span>
                            <span class="text-body-xs text-on-surface-variant" x-text="item.type"></span>
                            <button type="button" @click="remove(item.id)" class="p-1 hover:bg-surface-container rounded transition text-error">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    </template>
                </div>
                <p class="mt-space-sm text-body-sm text-on-surface-variant" x-show="selectedItems.length === 0">
                    No material selected yet.
                </p>

                <!-- Explorer Modal -->
                <div
                    x-show="open"
                    x-cloak
                    @click.self="open = false"
                    @keydown.escape.window="open = false"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
                >
                    <div class="bg-surface border border-outline-variant rounded-lg max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden" @click.stop>
                        <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                            <h3 class="font-label-lg text-label-lg text-on-surface">Choose Learning Material</h3>
                            <button type="button" @click="open = false" class="text-on-surface-variant hover:text-on-surface">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>

                        <div class="px-space-lg py-space-md border-b border-outline-variant">
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-space-md top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="materialSearch"
                                    placeholder="Search media..."
                                    class="w-full pl-10 pr-space-md py-space-sm border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                                />
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-space-lg">
                            <!-- Skeleton Loading -->
                            <div
                                wire:loading.delay.class.remove="hidden"
                                wire:target="materialSearch"
                                class="hidden grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-space-md animate-pulse"
                            >
                                @for ($i = 0; $i < 12; $i++)
                                    <div class="flex flex-col items-center gap-space-xs p-space-sm">
                                        <div class="w-full aspect-square rounded-md bg-surface-container"></div>
                                        <div class="h-2 bg-surface-container rounded w-3/4"></div>
                                    </div>
                                @endfor
                            </div>

                            <div wire:loading.remove wire:target="materialSearch">
                                @if ($mediaItems->isEmpty())
                                    <p class="p-space-lg text-center text-body-sm text-on-surface-variant">No media found.</p>
                                @else
                                    <div class="grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-space-md">
                                        @foreach ($mediaItems as $item)
                                            <label
                                                wire:key="explorer-material-{{ $item->id }}"
                                                @click.prevent="toggle({ id: '{{ $item->id }}', title: @js($item->title), type: '{{ $item->type->value }}' })"
                                                class="flex flex-col items-center gap-space-xs p-space-sm rounded-lg border cursor-pointer hover:bg-surface-container/50 transition-colors"
                                                :class="selectedIds.includes('{{ $item->id }}') ? 'border-primary bg-primary/5' : 'border-transparent'"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedIds.includes('{{ $item->id }}')"
                                                    class="sr-only"
                                                />
                                                <div class="w-full aspect-square rounded-md bg-surface-container flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-on-surface-variant text-[28px]">description</span>
                                                </div>
                                                <p class="w-full text-body-xs text-on-surface text-center line-clamp-2 break-words leading-tight">
                                                    {{ $item->title }}
                                                </p>
                                                <span class="text-body-xs text-on-surface-variant">{{ $item->type->value }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="px-space-lg py-space-md border-t border-outline-variant flex items-center justify-between">
                            <p class="text-body-sm text-on-surface-variant"><span x-text="selectedItems.length"></span> selected</p>
                            <button
                                type="button"
                                @click="open = false"
                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                            >
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Conferences -->
            @if ($deliveryMode === \App\Enums\DeliveryMode::VirtualClass->value)
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
            @endif

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

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('materialPicker', (config) => ({
                open: false,
                selectedItems: config.initialSelected || [],

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
                    this.$wire.set('selectedMaterialIds', this.selectedIds, false);
                },
            }));
        });
    </script>
@endpush
