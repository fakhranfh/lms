@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{
        bands: { a: {{ $course->grade_band_a_min }}, b: {{ $course->grade_band_b_min }}, c: {{ $course->grade_band_c_min }}, d: {{ $course->grade_band_d_min }} },
        showConfirmModal: false,
        saving: false,
        get hasOutOfRangeBand() {
            return Object.values(this.bands).some((v) => v === '' || v === null || Number(v) < 0 || Number(v) > 100);
        },
        get isDescending() {
            return Number(this.bands.a) > Number(this.bands.b) && Number(this.bands.b) > Number(this.bands.c) && Number(this.bands.c) > Number(this.bands.d);
        },
        get isValid() {
            return ! this.hasOutOfRangeBand && this.isDescending;
        },
    }"
>
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    @if ($successMessage)
        <div wire:click="clearSuccessMessage" class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md cursor-pointer">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <div class="flex items-center gap-space-md">
        <a href="{{ route('gradebook.index', $course) }}" wire:navigate class="text-on-surface-variant hover:text-on-surface transition">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface">Edit Grade Range</h1>
    </div>

    <div class="space-y-space-lg">
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
            @foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                <div class="p-space-lg flex items-center justify-between gap-space-md">
                    <label for="band-{{ $key }}" class="font-title-sm text-title-sm text-on-surface">Grade {{ $label }} (minimum score)</label>
                    <div class="flex-shrink-0 relative w-24">
                        <input
                            id="band-{{ $key }}"
                            type="number"
                            min="0"
                            max="100"
                            step="1"
                            x-model.number="bands.{{ $key }}"
                            :class="(bands.{{ $key }} === '' || bands.{{ $key }} === null || Number(bands.{{ $key }}) < 0 || Number(bands.{{ $key }}) > 100) ? 'border-error' : 'border-outline'"
                            class="w-full text-right pr-7 pl-space-md py-space-xs border rounded-full font-label-sm text-label-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                        />
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-label-sm text-on-surface-variant">%</span>
                    </div>
                </div>
            @endforeach

            <div class="p-space-lg">
                <p class="font-body-sm text-body-sm text-on-surface-variant">Grade E is anything below the Grade D minimum.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-space-md">
            <p class="font-label-sm text-label-sm text-error" x-show="!isValid" x-cloak>
                <span x-show="hasOutOfRangeBand" x-cloak>Each threshold must be 0–100</span>
                <span x-show="!hasOutOfRangeBand && !isDescending" x-cloak>Thresholds must strictly decrease from A to D</span>
            </p>
            <button
                type="button"
                :disabled="!isValid"
                @click="showConfirmModal = true"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-40 disabled:cursor-not-allowed"
            >
                Save Changes
            </button>
        </div>
    </div>

    <!-- Grade Range Change Confirmation Modal -->
    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="!saving && (showConfirmModal = false)"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        ></div>

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">warning</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Save Grade Range Changes</h3>
                        <p class="font-body-sm text-body-sm text-error">
                            Saving will recalculate letter grades for every student in this course. This action cannot be undone.
                        </p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            type="button"
                            :disabled="saving"
                            @click="showConfirmModal = false"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="saving"
                            @click="
                                saving = true;
                                $wire.call('save', bands).finally(() => { saving = false; showConfirmModal = false; });
                            "
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-60 disabled:cursor-not-allowed inline-flex items-center justify-center gap-space-sm"
                        >
                            <svg x-show="saving" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span x-text="saving ? 'Saving...' : 'Save & Recalculate'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
