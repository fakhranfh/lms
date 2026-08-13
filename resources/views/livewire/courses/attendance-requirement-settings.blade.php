@section('title', 'Attendance Settings')

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <div>
        <a href="{{ route('attendance.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Attendance
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">Attendance Settings</h1>
    </div>

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Minimal Attendance -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md max-w-md">
        <h2 class="font-label-lg text-label-lg text-on-surface">Minimal Attendance</h2>
        <p class="text-body-sm text-on-surface-variant">Minimum number of sessions a student is required to attend for this course.</p>
        <form wire:submit="saveMinimalAttendance" class="flex items-end gap-space-md">
            <div class="flex-1">
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Sessions</label>
                <input type="number" min="0" wire:model="minimalAttendance" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                @error('minimalAttendance') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="saveMinimalAttendance"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span wire:loading wire:target="saveMinimalAttendance" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Save
            </button>
        </form>
    </div>

    <!-- Attendance Requirements -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
        <h2 class="font-label-lg text-label-lg text-on-surface">Attendance Requirements</h2>
        <p class="text-body-sm text-on-surface-variant">Conditions that must be fulfilled for a session to count as attended. If none are configured, attendance falls back to a manual check-in.</p>

        <div class="divide-y divide-outline-variant border border-outline-variant rounded-lg overflow-hidden">
            @forelse ($requirements as $index => $requirement)
                <div wire:key="requirement-{{ $requirement->id }}" class="flex items-center justify-between gap-space-md px-space-md py-space-sm">
                    <div>
                        <p class="font-label-md text-label-md text-on-surface">{{ $requirement->label }}</p>
                        <p class="text-body-xs text-on-surface-variant">{{ str($requirement->requirement_type->value)->replace('_', ' ')->title() }}</p>
                    </div>
                    <div class="flex items-center gap-space-xs">
                        <button type="button" wire:click="moveRequirement('{{ $requirement->id }}', 'up')" @disabled($index === 0) class="p-1 hover:bg-surface-container rounded transition disabled:opacity-30">
                            <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                        </button>
                        <button type="button" wire:click="moveRequirement('{{ $requirement->id }}', 'down')" @disabled($index === $requirements->count() - 1) class="p-1 hover:bg-surface-container rounded transition disabled:opacity-30">
                            <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                        </button>
                        <button type="button" wire:click="deleteRequirement('{{ $requirement->id }}')" class="p-1 hover:bg-surface-container rounded transition text-error">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>
            @empty
                <p class="px-space-md py-space-md text-body-sm text-on-surface-variant">No requirements configured yet.</p>
            @endforelse
        </div>

        <form wire:submit="addRequirement" class="flex items-end gap-space-md pt-space-sm">
            <div class="flex-1">
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Type</label>
                <select wire:model="requirementType" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                    @foreach ($requirementTypes as $type)
                        <option value="{{ $type->value }}">{{ str($type->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Label</label>
                <input type="text" wire:model="label" placeholder="e.g. Forum Completed" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                @error('label') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="addRequirement"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span wire:loading wire:target="addRequirement" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Add
            </button>
        </form>
    </div>
</div>
