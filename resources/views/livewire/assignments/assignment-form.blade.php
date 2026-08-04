@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="w-full space-y-space-lg">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
            <a href="{{ route('courses.index') }}" class="hover:text-on-surface transition">Courses</a>
            <span>/</span>
            <a href="{{ route('courses.modules', $lesson->module->course) }}" class="hover:text-on-surface transition">{{ $lesson->module->course->title }}</a>
            <span>/</span>
            <a href="{{ route('courses.modules', $lesson->module->course) }}" class="hover:text-on-surface transition">{{ $lesson->module->title }}</a>
            <span>/</span>
            <a href="{{ route('lessons.edit', $lesson) }}" class="hover:text-on-surface transition">{{ $lesson->title }}</a>
            <span>/</span>
            <span class="text-on-surface font-medium">{{ $pageTitle }}</span>
        </nav>

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

        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-headline-sm text-headline-sm text-on-surface">{{ $pageTitle }}</h1>
                <p class="text-body-sm text-on-surface-variant mt-1">{{ $lesson->title }}</p>
            </div>
            <a
                href="{{ route('lessons.edit', $lesson) }}"
                class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors flex-shrink-0"
            >
                Back to Lesson
            </a>
        </div>

        <form wire:submit="save" class="space-y-space-lg">
            <div>
                <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Title</label>
                <input type="text" wire:model="title" class="w-full px-space-md py-space-sm border border-outline rounded-lg" />
                @error('title') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Prompt Question</label>
                <textarea wire:model="promptQuestion" rows="4" class="w-full px-space-md py-space-sm border border-outline rounded-lg"></textarea>
                @error('promptQuestion') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Max Score</label>
                    <input type="number" step="0.01" wire:model="maxScore" class="w-full px-space-md py-space-sm border border-outline rounded-lg" />
                    @error('maxScore') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-label-md font-label-md text-on-surface mb-space-sm">Passing Score</label>
                    <input type="number" step="0.01" wire:model="passingScore" class="w-full px-space-md py-space-sm border border-outline rounded-lg" />
                    @error('passingScore') <p class="text-error text-body-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-space-lg">
                <label class="flex items-center gap-space-sm">
                    <input type="checkbox" wire:model="isPublished" />
                    <span class="text-body-sm text-on-surface">Published</span>
                </label>
                <label class="flex items-center gap-space-sm">
                    <input type="checkbox" wire:model="allowMultipleSubmissions" />
                    <span class="text-body-sm text-on-surface">Allow multiple submissions</span>
                </label>
            </div>

            <div class="border border-outline rounded-lg p-space-lg space-y-space-md">
                <div class="flex items-center justify-between">
                    <h2 class="font-title-md text-title-md text-on-surface">Rubric</h2>
                    <span class="text-body-sm {{ round($this->totalWeight, 2) === 100.0 ? 'text-success' : 'text-error' }}">
                        Total weight: {{ $this->totalWeight }} / 100
                    </span>
                </div>

                @foreach ($rubricItems as $index => $item)
                    <div class="grid grid-cols-12 gap-space-sm items-start border-b border-outline/30 pb-space-md">
                        <div class="col-span-4">
                            <input type="text" placeholder="Criterion" wire:model="rubricItems.{{ $index }}.criterion" class="w-full px-space-sm py-space-xs border border-outline rounded-lg text-body-sm" />
                        </div>
                        <div class="col-span-2">
                            <input type="number" placeholder="Weight %" wire:model="rubricItems.{{ $index }}.weight" class="w-full px-space-sm py-space-xs border border-outline rounded-lg text-body-sm" />
                        </div>
                        <div class="col-span-2">
                            <input type="number" placeholder="Max points" wire:model="rubricItems.{{ $index }}.max_points" class="w-full px-space-sm py-space-xs border border-outline rounded-lg text-body-sm" />
                        </div>
                        <div class="col-span-3">
                            <input type="text" placeholder="Description" wire:model="rubricItems.{{ $index }}.description" class="w-full px-space-sm py-space-xs border border-outline rounded-lg text-body-sm" />
                        </div>
                        <div class="col-span-1 text-right">
                            <button type="button" wire:click="removeRubricItem({{ $index }})" class="text-error">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </div>
                @endforeach

                <button type="button" wire:click="addRubricItem" class="text-primary text-label-sm font-label-md inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add rubric item
                </button>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-space-sm">
                    <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                        Save Assignment
                    </button>

                    @if ($assignment)
                        @if ($isPublished)
                            <button type="button" wire:click="unpublish" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md">Unpublish</button>
                        @else
                            <button type="button" wire:click="publish" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md">Publish</button>
                        @endif
                    @endif
                </div>

                @if ($assignment)
                    <button type="button"
                        @click="$dispatch('open-delete-confirm', { id: '{{ $assignment->id }}', name: @js($assignment->title), type: 'assignments' })"
                        class="px-space-lg py-space-sm text-error font-label-md text-label-md">
                        Delete
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
