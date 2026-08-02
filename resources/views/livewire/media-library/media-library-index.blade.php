@section('title', 'Media Library')

<div
    class="space-y-space-lg"
    x-data="mediaLibraryUploader({ extensionTypeMap: @js($extensionTypeMap) })"
>
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

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Media Library</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Upload and manage material files used across your LMS</p>
        </div>
    </div>

    <!-- Quota -->
    @php $quotaInfo = $this->getQuotaInfo(); @endphp
    <div class="p-space-md bg-surface-container rounded-lg border border-outline text-body-sm space-y-space-xs">
        <p class="{{ $quotaInfo['color'] }}">
            Remaining quota: {{ $quotaInfo['remaining'] }}
            @if ($quotaInfo['limit_gb'])
                of {{ $quotaInfo['limit_gb'] }} GB
            @endif
            ({{ $quotaInfo['percentage'] }}% used)
        </p>
        @if ($quotaInfo['warning'])
            <p class="{{ $quotaInfo['color'] }} font-medium">{{ $quotaInfo['warning'] }}</p>
        @endif
    </div>

    <template x-if="clientError">
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg text-body-sm text-error" x-text="clientError"></div>
    </template>

    <!-- Upload -->
    @can('media.create')
        <div class="p-space-lg bg-surface-container rounded-lg border-2 border-dashed border-outline">
            <div class="text-center">
                <input
                    type="file"
                    id="mediaFile"
                    x-ref="mediaFile"
                    accept="{{ $acceptedExtensions }}"
                    @change="upload($refs.mediaFile.files[0])"
                    :disabled="uploading"
                    class="hidden"
                />
                <button
                    type="button"
                    @click="$refs.mediaFile.click()"
                    :disabled="uploading"
                    class="text-body-md text-primary font-medium hover:underline disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span x-show="! uploading">Upload Media</span>
                    <span x-show="uploading" x-cloak x-text="statusText + (statusText === 'Uploading...' ? ' (' + progress + '%)' : '')"></span>
                </button>

                <template x-if="uploading">
                    <div class="mt-space-md space-y-space-sm">
                        <div class="w-full h-2 bg-surface rounded-full overflow-hidden">
                            <div class="h-full bg-primary transition-all duration-150" :style="`width: ${progress}%`"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endcan

    <!-- Filters -->
    <div class="flex gap-space-md">
        <div class="flex-1 relative">
            <span class="material-symbols-outlined absolute left-space-lg top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search media..."
                class="w-full pl-12 pr-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
        </div>
        <select
            wire:model.live="typeFilter"
            class="px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
        >
            <option value="">All types</option>
            @foreach ($materialTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
        <select
            wire:model.live="perPage"
            class="px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
        >
            @foreach ($this->getPerPageOptions() as $option)
                <option value="{{ $option }}">{{ $option }} / page</option>
            @endforeach
        </select>
    </div>

    <!-- Bulk actions (selection is tracked client-side; only the final delete hits the server) -->
    @can('media.delete')
        <div class="flex items-center justify-between gap-space-md flex-wrap" x-show="{{ $items->isNotEmpty() ? 'true' : 'false' }}">
            <div class="flex items-center gap-space-md text-body-sm">
                <button type="button" @click="selectAllOnPage()" class="text-primary hover:underline">
                    Select all on this page
                </button>
                <button type="button" x-show="selectedIds.length > 0" @click="selectedIds = []" class="text-on-surface-variant hover:underline">
                    Clear (<span x-text="selectedIds.length"></span> selected)
                </button>
            </div>
            <button
                type="button"
                x-show="selectedIds.length > 0"
                @click="bulkDeleteConfirmOpen = true"
                :disabled="bulkDeleting"
                class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-xs disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="! bulkDeleting" class="material-symbols-outlined text-[18px]">delete</span>
                <span x-show="bulkDeleting" x-cloak class="inline-block animate-spin">⟳</span>
                Delete Selected
            </button>
        </div>
    @endcan

    <!-- Skeleton Loading -->
    <div
        wire:loading.delay.class.remove="hidden"
        wire:target="{{ $refreshTargets }}"
        class="hidden grid grid-cols-[repeat(auto-fill,minmax(70px,1fr))] gap-space-xs animate-pulse"
    >
        @for ($i = 0; $i < 18; $i++)
            <div class="flex flex-col items-center gap-space-xs p-space-xs">
                <div class="w-full aspect-square rounded-md bg-surface-container"></div>
                <div class="h-2 bg-surface-container rounded w-3/4"></div>
            </div>
        @endfor
    </div>

    <div wire:loading.remove wire:target="{{ $refreshTargets }}">
        @if ($items->isEmpty())
            <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
                @if ($search || $typeFilter)
                    <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">search_off</span>
                    <p class="text-body-md text-on-surface-variant mb-4">No media found matching your filters</p>
                    <button wire:click="$set('search', ''); $set('typeFilter', '')" type="button" class="text-primary font-medium hover:underline">
                        Clear filters
                    </button>
                @else
                    <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">perm_media</span>
                    <p class="text-body-md text-on-surface-variant">No media uploaded yet. Upload your first file to get started.</p>
                @endif
            </div>
        @else
            <div x-ref="mediaGrid" class="grid grid-cols-[repeat(auto-fill,minmax(70px,1fr))] gap-space-xs">
                @foreach ($items as $item)
                    <div wire:key="media-{{ $item->id }}" class="relative">
                        @can('media.delete')
                            <label
                                class="absolute top-1 left-1 z-10 flex items-center justify-center w-5 h-5 rounded bg-surface/90 border border-outline cursor-pointer"
                                @click.stop
                            >
                                <input
                                    type="checkbox"
                                    class="media-select-checkbox w-4 h-4"
                                    value="{{ $item->id }}"
                                    :checked="selectedIds.includes('{{ $item->id }}')"
                                    @change="toggleSelect('{{ $item->id }}')"
                                />
                            </label>
                        @endcan
                        <button
                            type="button"
                            @click="previewOpen = false; selectedMedia = {
                                id: '{{ $item->id }}',
                                title: @js($item->title),
                                type: @js($item->type->value),
                                icon: @js($this->getMaterialIcon($item->type)),
                                isImage: {{ $item->type->value === 'Image' ? 'true' : 'false' }},
                                size: @js($this->formatBytes($item->file_size)),
                                uploader: @js($item->uploader?->name),
                                createdAt: @js($item->created_at->format('M d, Y H:i')),
                                url: @js($item->file_url),
                            }"
                            class="group w-full flex flex-col items-center gap-space-xs p-space-xs rounded-lg border border-transparent hover:border-primary/40 hover:bg-surface-container/60 transition-colors text-left"
                            :class="{ 'border-primary bg-primary/5': selectedMedia?.id === '{{ $item->id }}' }"
                        >
                            <div class="w-full aspect-square rounded-md overflow-hidden bg-surface-container flex items-center justify-center">
                                @if ($item->type->value === 'Image')
                                    <img src="{{ $item->file_url }}" alt="{{ $item->title }}" class="w-full h-full object-cover" loading="lazy" />
                                @else
                                    <span class="text-xl">{{ $this->getMaterialIcon($item->type) }}</span>
                                @endif
                            </div>
                            <p class="w-full text-body-xs text-on-surface text-center line-clamp-2 break-words leading-tight">
                                {{ $item->title }}
                            </p>
                        </button>
                    </div>
                @endforeach
            </div>

            @if ($items->hasPages())
                <div class="flex items-center justify-between mt-space-lg">
                    <p class="text-body-sm text-on-surface-variant">
                        Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} media items
                    </p>
                    {{ $items->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Media Detail Modal -->
    <div
        x-show="selectedMedia"
        x-cloak
        @click.self="selectedMedia = null; previewOpen = false"
        @keydown.escape.window="if (! previewOpen) { selectedMedia = null }"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
    >
        <template x-if="selectedMedia">
            <div class="bg-surface border border-outline-variant rounded-lg max-w-md w-full overflow-hidden">
                <div class="aspect-video bg-surface-container flex items-center justify-center overflow-hidden">
                    <template x-if="selectedMedia.isImage">
                        <img :src="selectedMedia.url" :alt="selectedMedia.title" class="w-full h-full object-contain" />
                    </template>
                    <template x-if="! selectedMedia.isImage">
                        <span class="text-6xl" x-text="selectedMedia.icon"></span>
                    </template>
                </div>
                <div class="p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <h3 class="font-label-lg text-label-lg text-on-surface break-words" x-text="selectedMedia.title"></h3>
                        <button type="button" @click="selectedMedia = null; previewOpen = false" class="text-on-surface-variant hover:text-on-surface flex-shrink-0">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="text-body-sm text-on-surface-variant space-y-space-xs">
                        <p x-text="selectedMedia.type + ' • ' + selectedMedia.size"></p>
                        <p x-show="selectedMedia.uploader" x-text="'Uploaded by ' + selectedMedia.uploader"></p>
                        <p x-text="'Uploaded on ' + selectedMedia.createdAt"></p>
                    </div>
                    <div class="flex items-center gap-space-md pt-space-sm">
                        <button
                            type="button"
                            @click="previewOpen = true"
                            class="flex-1 px-space-lg py-space-sm border border-outline text-on-surface rounded-lg font-label-md text-label-md text-center hover:bg-surface-container transition-colors inline-flex items-center justify-center gap-space-xs"
                        >
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                            Preview
                        </button>
                        <a
                            :href="selectedMedia.url"
                            download
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md text-center hover:opacity-90 transition-opacity inline-flex items-center justify-center gap-space-xs"
                        >
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            Download
                        </a>
                        @can('media.delete')
                            <button
                                type="button"
                                @click="$dispatch('open-delete-confirm', { id: selectedMedia.id, name: selectedMedia.title, type: 'media' }); selectedMedia = null"
                                class="px-space-lg py-space-sm border border-error text-error rounded-lg font-label-md text-label-md hover:bg-error/10 transition"
                            >
                                Delete
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Media Preview Modal (same per-type viewers as the lesson material viewer) -->
    <div
        x-show="previewOpen && selectedMedia"
        x-cloak
        @click.self="previewOpen = false"
        @keydown.escape.window="previewOpen = false"
        class="fixed inset-0 z-[110] flex items-center justify-center bg-black/70 px-gutter"
    >
        <template x-if="previewOpen && selectedMedia">
            <div class="bg-surface border border-outline-variant rounded-lg max-w-4xl w-full overflow-hidden">
                <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                    <h3 class="font-label-lg text-label-lg text-on-surface truncate" x-text="selectedMedia.title"></h3>
                    <button type="button" @click="previewOpen = false" class="text-on-surface-variant hover:text-on-surface flex-shrink-0">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div
                    class="bg-surface-container"
                    :class="selectedMedia.type === 'Markdown' ? 'max-h-[75vh] overflow-y-auto' : 'aspect-video overflow-hidden'"
                >
                    <template x-if="selectedMedia.type === 'Video'">
                        <video :src="selectedMedia.url" width="100%" height="100%" controls class="w-full h-full">
                            Your browser does not support the video tag.
                        </video>
                    </template>

                    <template x-if="selectedMedia.type === 'PDF'">
                        <embed :src="selectedMedia.url" type="application/pdf" width="100%" height="100%" class="rounded" />
                    </template>

                    <template x-if="selectedMedia.type === 'Audio'">
                        <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                            <span class="text-5xl">🎵</span>
                            <p class="text-body-md text-on-surface" x-text="selectedMedia.title"></p>
                            <audio :src="selectedMedia.url" controls class="w-full">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                    </template>

                    <template x-if="selectedMedia.type === 'Image'">
                        <div class="w-full h-full flex items-center justify-center overflow-auto">
                            <img :src="selectedMedia.url" :alt="selectedMedia.title" class="max-w-full max-h-full" />
                        </div>
                    </template>

                    <template x-if="selectedMedia.type === 'Interactive'">
                        <iframe
                            :src="selectedMedia.url"
                            class="w-full h-full rounded border-0"
                            sandbox="allow-scripts allow-same-origin allow-forms"
                            :title="selectedMedia.title"
                        ></iframe>
                    </template>

                    <template x-if="selectedMedia.type === 'Presentation'">
                        <iframe
                            :src="'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(selectedMedia.url)"
                            width="100%"
                            height="100%"
                            frameborder="0"
                            class="rounded"
                        ></iframe>
                    </template>

                    <template x-if="selectedMedia.type === 'Document'">
                        <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                            <span class="text-5xl">📝</span>
                            <p class="text-body-md text-on-surface" x-text="selectedMedia.title"></p>
                            <a
                                :href="selectedMedia.url"
                                download
                                class="px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition"
                            >
                                Download Document
                            </a>
                        </div>
                    </template>

                    <template x-if="selectedMedia.type === 'Markdown'">
                        <div
                            x-data="{ html: null, error: null }"
                            x-init="
                                fetch(selectedMedia.url)
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
        </template>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div
        x-show="bulkDeleteConfirmOpen"
        x-cloak
        @click.self="bulkDeleteConfirmOpen = false"
        @keydown.escape.window="bulkDeleteConfirmOpen = false"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
    >
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Delete confirmation</h2>
            <p class="font-body-md text-body-md text-secondary">
                Are you sure you want to delete <span class="font-medium" x-text="selectedIds.length"></span> selected media item(s)?
                This action cannot be undone.
            </p>
            <div class="flex items-center justify-end gap-space-md">
                <button type="button" @click="bulkDeleteConfirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">
                    Cancel
                </button>
                <button
                    type="button"
                    @click="performBulkDelete()"
                    :disabled="bulkDeleting"
                    class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @include('partials.markdown-renderer-script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mediaLibraryUploader', (config) => ({
                extensionTypeMap: config.extensionTypeMap || {},
                uploading: false,
                progress: 0,
                statusText: '',
                clientError: null,
                selectedMedia: null,
                previewOpen: false,
                selectedIds: [],
                bulkDeleteConfirmOpen: false,
                bulkDeleting: false,

                toggleSelect(id) {
                    this.selectedIds = this.selectedIds.includes(id)
                        ? this.selectedIds.filter((selectedId) => selectedId !== id)
                        : [...this.selectedIds, id];
                },

                selectAllOnPage() {
                    const ids = Array.from(this.$refs.mediaGrid?.querySelectorAll('.media-select-checkbox') || [])
                        .map((checkbox) => checkbox.value);

                    this.selectedIds = Array.from(new Set([...this.selectedIds, ...ids]));
                },

                async performBulkDelete() {
                    this.bulkDeleteConfirmOpen = false;
                    this.bulkDeleting = true;

                    try {
                        await this.$wire.bulkDelete(this.selectedIds);
                        this.selectedIds = [];
                    } finally {
                        this.bulkDeleting = false;
                    }
                },

                async upload(file) {
                    if (! file) return;

                    this.clientError = null;

                    const extension = file.name.split('.').pop().toLowerCase();
                    const materialType = this.extensionTypeMap[extension];

                    if (! materialType) {
                        this.clientError = 'Unsupported file type: .' + extension;
                        this.$refs.mediaFile.value = '';

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
                        }
                    } catch (error) {
                        this.clientError = error.message || 'Upload failed';
                    } finally {
                        this.uploading = false;
                        this.progress = 0;
                        this.$refs.mediaFile.value = '';
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
