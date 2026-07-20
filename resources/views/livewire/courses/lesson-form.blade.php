@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="max-w-3xl mx-auto">
        <!-- Breadcrumb -->
        <div class="mb-space-lg">
            <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
                <a href="{{ route('courses.index') }}" class="hover:text-on-surface transition">Courses</a>
                <span>/</span>
                <a href="{{ route('courses.show', $module->course) }}" class="hover:text-on-surface transition">{{ $module->course->title }}</a>
                <span>/</span>
                <a href="{{ route('courses.show', $module->course) }}" class="hover:text-on-surface transition">{{ $module->title }}</a>
                <span>/</span>
                <span class="text-on-surface font-medium">{{ $pageTitle }}</span>
            </nav>
        </div>

        <!-- Header -->
        <div class="mb-space-xl flex items-center justify-between">
            <div>
                <h1 class="font-headline-md text-headline-md text-on-surface">
                    {{ $pageTitle }}
                </h1>
                <p class="text-body-md text-on-surface-variant mt-space-sm">
                    in <strong>{{ $module->title }}</strong> / {{ $module->course->title }}
                </p>
            </div>
            <a
                href="{{ route('courses.show', $module->course) }}"
                class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors flex-shrink-0"
            >
                Back to Course
            </a>
        </div>

        <!-- Form -->
        <form wire:submit="save" class="space-y-space-lg">
            <!-- Title -->
            <div>
                <label for="title" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Lesson Title <span class="text-error">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    placeholder="e.g., Introduction to HTML Tags"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('title') border-error @enderror"
                />
                @error('title')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Content
                </label>
                <textarea
                    id="content"
                    wire:model="content"
                    placeholder="Lesson content (supports HTML)..."
                    rows="8"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 font-mono @error('content') border-error @enderror"
                ></textarea>
                <p class="text-body-sm text-on-surface-variant mt-space-sm">
                    You can use HTML tags for formatting. A rich text editor can be integrated later.
                </p>
                @error('content')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Materials -->
            <div
                x-data="lessonMaterialUploader({
                    extensionTypeMap: @js($extensionTypeMap),
                })"
            >
                <label class="block text-label-md text-on-surface mb-space-md font-label-md">
                    Lesson Materials
                </label>

                @if ($errorMessage)
                    <div class="mb-space-md p-space-md bg-error/10 border border-error text-error rounded-lg text-body-sm">
                        {{ $errorMessage }}
                    </div>
                @endif

                <template x-if="clientError">
                    <div class="mb-space-md p-space-md bg-error/10 border border-error text-error rounded-lg text-body-sm" x-text="clientError"></div>
                </template>

                @unless ($lesson)
                    <div class="mb-space-md p-space-md bg-surface-container rounded-lg border border-outline text-body-sm text-on-surface-variant">
                        Uploading a material will automatically save this lesson as a draft. You can keep editing and publish it when ready.
                    </div>
                @endunless

                <!-- Existing Materials -->
                @if ($materials->count() > 0)
                    <div class="mb-space-md space-y-space-sm">
                        <h4 class="text-label-md text-on-surface font-label-md">Current Materials</h4>
                        <div class="space-y-space-xs">
                            @foreach ($materials as $material)
                                <div class="flex items-center justify-between p-space-md bg-surface-container rounded-lg">
                                    <div class="flex items-center gap-space-md flex-1">
                                        <span class="text-body-md">{{ $this->getMaterialIcon($material->type) }}</span>
                                        <div class="flex-1 min-w-0" x-data="{ savingTitle: false }">
                                            <div class="flex items-center gap-space-xs" :class="{ 'opacity-70': savingTitle }">
                                                <input
                                                    type="text"
                                                    value="{{ $material->title }}"
                                                    :disabled="savingTitle"
                                                    @keydown.enter.prevent="$event.target.blur()"
                                                    @change="savingTitle = true; await $wire.updateMaterialTitle('{{ $material->id }}', $event.target.value); savingTitle = false"
                                                    wire:key="material-title-{{ $material->id }}"
                                                    class="w-full bg-transparent text-body-sm text-on-surface font-medium truncate px-space-xs -mx-space-xs rounded border border-transparent hover:border-outline focus:border-primary focus:outline-none focus:bg-surface disabled:cursor-wait"
                                                />
                                                <span x-show="savingTitle" x-cloak class="inline-block animate-spin text-on-surface-variant flex-shrink-0">⟳</span>
                                            </div>
                                            <p class="text-body-sm text-on-surface-variant">{{ $material->type->value }} • {{ $this->formatBytes($material->file_size) }}</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="deleteMaterial('{{ $material->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="deleteMaterial('{{ $material->id }}')"
                                        class="ml-space-md px-space-md py-space-sm text-error hover:bg-error/10 rounded transition flex items-center gap-space-xs disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="deleteMaterial('{{ $material->id }}')">Delete</span>
                                        <span wire:loading wire:target="deleteMaterial('{{ $material->id }}')" class="inline-block animate-spin">⟳</span>
                                        <span wire:loading wire:target="deleteMaterial('{{ $material->id }}')">Deleting...</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Upload New Material -->
                <div class="p-space-lg bg-surface-container rounded-lg border-2 border-dashed border-outline">
                    <div class="text-center">
                        <input
                            type="file"
                            id="materialFile"
                            x-ref="materialFile"
                            accept="{{ $acceptedExtensions }}"
                            @change="upload($refs.materialFile.files[0])"
                            :disabled="uploading"
                            class="hidden"
                        />
                        <button
                            type="button"
                            @click="$refs.materialFile.click()"
                            :disabled="uploading"
                            class="text-body-md text-primary font-medium hover:underline disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Upload Lesson Material
                        </button>

                        <!-- Upload Progress -->
                        <template x-if="uploading">
                            <div class="mt-space-md space-y-space-sm">
                                <div class="w-full h-2 bg-surface rounded-full overflow-hidden">
                                    <div class="h-full bg-primary transition-all duration-150" :style="`width: ${progress}%`"></div>
                                </div>
                                <p class="text-body-sm text-on-surface-variant">
                                    <span x-text="statusText"></span>
                                    <template x-if="statusText === 'Uploading...'"> (<span x-text="progress"></span>%)</template>
                                </p>
                            </div>
                        </template>

                        <!-- Upload Preview -->
                        <template x-if="! uploading && preview">
                            <div class="mt-space-md p-space-md bg-surface rounded-lg border border-outline flex items-center gap-space-md text-left">
                                <template x-if="preview.isImage">
                                    <img :src="preview.url" class="w-12 h-12 rounded object-cover flex-shrink-0" />
                                </template>
                                <template x-if="! preview.isImage">
                                    <span class="text-3xl flex-shrink-0" x-text="preview.icon"></span>
                                </template>
                                <div class="flex-1 min-w-0 text-left">
                                    <p class="text-body-sm text-on-surface font-medium truncate" x-text="preview.name"></p>
                                    <p class="text-body-sm text-success flex items-center gap-space-xs">
                                        <span>✓</span>
                                        <span>Uploaded successfully</span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Duration -->
            <div>
                <label for="durationMinutes" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Duration <span class="text-secondary text-body-sm">(minutes - optional)</span>
                </label>
                <input
                    type="number"
                    id="durationMinutes"
                    wire:model="durationMinutes"
                    placeholder="e.g., 15"
                    min="1"
                    max="480"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('durationMinutes') border-error @enderror"
                />
                <p class="text-body-sm text-on-surface-variant mt-space-sm">
                    Estimated reading/viewing time for this lesson
                </p>
                @error('durationMinutes')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Publish Status -->
            <div class="flex items-center gap-space-md p-space-lg bg-surface-container rounded-lg">
                <div>
                    <input
                        type="checkbox"
                        id="isPublished"
                        wire:model="isPublished"
                        class="rounded"
                    />
                </div>
                <div class="flex-1">
                    <label for="isPublished" class="block text-label-md text-on-surface font-label-md cursor-pointer">
                        Publish Lesson
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-space-xs">
                        Published lessons are visible to students. Unpublished lessons remain in draft mode.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ route('courses.show', $module->course) }}"
                    class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                >
                    {{ $lesson ? 'Update Lesson' : 'Create Lesson' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            const typeIcons = {
                Video: '🎥',
                PDF: '📄',
                Document: '📝',
                Audio: '🎵',
                Presentation: '📊',
                Image: '🖼️',
                Interactive: '🎮',
                Markdown: '📄',
            };

            Alpine.data('lessonMaterialUploader', (config) => ({
                extensionTypeMap: config.extensionTypeMap || {},
                uploading: false,
                progress: 0,
                statusText: '',
                clientError: null,
                preview: null,

                async upload(file) {
                    if (! file) return;

                    this.clientError = null;
                    if (this.preview?.url) {
                        URL.revokeObjectURL(this.preview.url);
                    }
                    this.preview = null;

                    const extension = file.name.split('.').pop().toLowerCase();
                    const materialType = this.extensionTypeMap[extension];

                    if (! materialType) {
                        this.clientError = 'Unsupported file type: .' + extension;
                        this.$refs.materialFile.value = '';

                        return;
                    }

                    this.uploading = true;
                    this.progress = 0;
                    this.statusText = 'Preparing upload...';

                    try {
                        const result = await this.$wire.generateUploadUrl(file.name, materialType);

                        if (result.error) {
                            this.clientError = result.error;

                            return;
                        }

                        this.statusText = 'Uploading...';
                        await this.putFile(result.url, file);

                        this.statusText = 'Finalizing...';
                        const finalizeResult = await this.$wire.finalizeUpload({
                            type: materialType,
                            temp_key: result.key,
                            title: file.name.replace(/\.[^/.]+$/, ''),
                            description: '',
                        });

                        if (finalizeResult?.error) {
                            this.clientError = finalizeResult.error;

                            return;
                        }

                        this.preview = {
                            name: file.name,
                            isImage: materialType === 'Image',
                            icon: typeIcons[materialType] || '📄',
                            url: materialType === 'Image' ? URL.createObjectURL(file) : null,
                        };
                    } catch (error) {
                        this.clientError = error.message || 'Upload failed';
                    } finally {
                        this.uploading = false;
                        this.progress = 0;
                        this.$refs.materialFile.value = '';
                    }
                },

                putFile(url, file) {
                    return new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('PUT', url, true);
                        xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');

                        xhr.upload.addEventListener('progress', (event) => {
                            if (event.lengthComputable) {
                                this.progress = Math.round((event.loaded / event.total) * 100);
                            }
                        });

                        xhr.addEventListener('load', () => {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                resolve();
                            } else {
                                reject(new Error('Upload failed with status ' + xhr.status));
                            }
                        });

                        xhr.addEventListener('error', () => reject(new Error('Network error during upload')));

                        xhr.send(file);
                    });
                },
            }));
        });
    </script>
@endpush
