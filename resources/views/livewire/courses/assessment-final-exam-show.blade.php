@section('title', $assessment->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
    </div>

    @if ($successMessage)
        <template x-teleport="body">
            <div
                x-data="{ open: true }"
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
                @click.self="open = false; $wire.call('clearSuccessMessage')"
            >
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200 delay-75"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg text-center"
                >
                    <div class="mx-auto w-12 h-12 rounded-full bg-success/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-success text-[28px]" data-weight="fill">check_circle</span>
                    </div>
                    <div>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-space-xs">Submission successful</h2>
                        <p class="font-body-md text-body-md text-secondary">{{ $successMessage }}</p>
                    </div>
                    <button
                        type="button"
                        @click="open = false; $wire.call('clearSuccessMessage')"
                        class="w-full px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        OK
                    </button>
                </div>
            </div>
        </template>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Overview Card -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <!-- Header with title and badges -->
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-space-md mb-space-md">
                    <h1 class="font-headline-md text-headline-md text-on-surface">{{ $assessment->title }}</h1>
                    @if ($isExpired)
                        <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">
                            Expired
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-space-md">
                    <span class="inline-flex items-center gap-space-xs text-body-sm text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px]">
                            {{ $assessment->assigned_to->value === 'individual' ? 'person' : 'groups' }}
                        </span>
                        {{ str($assessment->assigned_to->value)->title() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Meta grid (2 columns) -->
        <div class="grid grid-cols-2 gap-space-lg border-t border-b border-outline-variant py-space-lg">
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Start</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($assessment->start_date)
                        {{ $assessment->start_date_display->format('M j, Y, H:i') }}
                    @else
                        <span class="text-on-surface-variant">—</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Due</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($assessment->end_date)
                        {{ $assessment->end_date_display->format('M j, Y, H:i') }}
                    @else
                        <span class="text-on-surface-variant">—</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Question</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $assessment->quiz && $assessment->quiz->questions->isNotEmpty() ? $assessment->quiz->questions->count() : $assessment->questions->count() }}
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Attempts</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($isStudent)
                        {{ $attemptsUsed }} of {{ $attemptLimit }} Attempts
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Scoring Method</p>
                <p class="text-body-sm text-on-surface font-medium">Latest Score</p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Exam Type</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $finalExam ? str($finalExam->exam_type->value)->replace('_', ' ')->title() : '—' }}
                </p>
            </div>
        </div>

        @if ($finalExam?->instructions)
            <div class="bg-surface-container/50 border border-outline-variant rounded-lg p-space-lg">
                <p class="font-label-md text-label-md text-on-surface mb-space-sm">Instructions</p>
                <div class="rte-content prose prose-sm max-w-none text-on-surface-variant">{!! $finalExam->instructions !!}</div>
            </div>
        @endif

        <!-- Status Message & Action Button -->
        @if ($isStudent)
            @if ($latestAttempt && $pendingProctorReview)
                <div class="p-space-lg bg-surface-container/50 border border-outline-variant rounded-lg">
                    <p class="text-body-sm text-on-surface-variant">Your submission is pending proctoring review. You'll be notified once it's graded.</p>
                </div>
            @elseif ($latestScore)
                <div class="p-space-lg {{ $isDisqualified ? 'bg-error/5 border border-error/20' : 'bg-success/5 border border-success/20' }} rounded-lg space-y-space-md">
                    <div class="flex items-center gap-space-sm">
                        <span class="material-symbols-outlined {{ $isDisqualified ? 'text-error' : 'text-success' }}" data-weight="fill">{{ $isDisqualified ? 'cancel' : 'check_circle' }}</span>
                        <p class="font-label-md text-label-md text-on-surface">Score: {{ rtrim(rtrim(number_format($latestScore->score, 2), '0'), '.') }}</p>
                    </div>

                    @if ($latestScore->feedback)
                        <div>
                            <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Feedback</p>
                            <p class="text-body-sm text-on-surface">{{ $latestScore->feedback }}</p>
                        </div>
                    @endif

                    @if ($isProctored && $latestProctorSession)
                        <div>
                            <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Proctoring Result</p>
                            <p class="text-body-sm text-on-surface font-medium">
                                {{ $latestProctorSession->review_decision ? str($latestProctorSession->review_decision->value)->replace('_', ' ')->title() : 'No Action' }}
                            </p>
                            @if ($latestProctorSession->review_notes)
                                <p class="text-body-sm text-on-surface-variant mt-space-xs">{{ $latestProctorSession->review_notes }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @elseif (!$canResubmit && $latestAttempt)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">Your submission is awaiting grading. You will be able to resubmit once graded.</p>
                    </div>
                </div>
            @elseif ($isExpired)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">The submission window for this assessment has closed.</p>
                    </div>
                </div>
            @elseif ($attemptLimit && $attemptsUsed >= $attemptLimit)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">You have reached the maximum number of attempts for this assessment.</p>
                    </div>
                </div>
            @elseif ($canSubmit && $canResubmit && $finalExam && in_array($finalExam->exam_type->value, ['open_book', 'closed_book']))
                @if ($assessment->quiz && $assessment->quiz->questions->isNotEmpty())
                    <div
                        x-data="{
                            confirmOpen: false,
                            navigating: false,
                            referenceFiles: @js($referenceFiles ?? []),
                            referenceExtensionTypeMap: @js($referenceExtensionTypeMap ?? []),
                            referenceUploads: [],
                            referenceRemovingIds: [],
                            referenceError: @js($referenceFileError ?? null),
                            referenceViewerOpen: false,
                            viewingReferenceFile: null,
                            confirmDeleteFile: null,
                            confirmDeleteAllOpen: false,
                            deletingAll: false,
                            openReferenceFile(file) {
                                this.viewingReferenceFile = file;
                                this.referenceViewerOpen = true;
                            },
                            closeReferenceFileViewer() {
                                this.referenceViewerOpen = false;
                                this.viewingReferenceFile = null;
                            },
                            uploadReferenceFiles(fileList) {
                                const files = Array.from(fileList || []);
                                if (files.length === 0) { return; }

                                this.referenceError = null;
                                files.forEach((file) => this.uploadReferenceFile(file));
                                this.$refs.referenceFileInput.value = '';
                            },
                            async uploadReferenceFile(file) {
                                const extension = file.name.split('.').pop().toLowerCase();
                                const materialType = this.referenceExtensionTypeMap[extension];

                                const upload = {
                                    key: file.name + '-' + Date.now() + '-' + Math.random().toString(36).slice(2),
                                    name: file.name,
                                    progress: 0,
                                    statusText: 'Preparing upload...',
                                    error: null,
                                };
                                this.referenceUploads = [...this.referenceUploads, upload];

                                if (! materialType) {
                                    upload.error = 'Unsupported file type: .' + extension;

                                    return;
                                }

                                try {
                                    const result = await $wire.generateReferenceFileUploadUrl(file.name, materialType);

                                    if (result.error) {
                                        upload.error = result.error;

                                        return;
                                    }

                                    upload.statusText = 'Uploading...';
                                    await this.putReferenceFile(result.url, file, upload);

                                    upload.statusText = 'Finalizing...';
                                    const finalizeResult = await $wire.finalizeReferenceFileUpload({
                                        type: materialType,
                                        temp_key: result.key,
                                        title: file.name,
                                    });

                                    if (finalizeResult?.error) {
                                        upload.error = finalizeResult.error;
                                    } else if (finalizeResult?.file) {
                                        this.referenceFiles = [...this.referenceFiles, finalizeResult.file];
                                        this.referenceUploads = this.referenceUploads.filter((u) => u.key !== upload.key);
                                    }
                                } catch (error) {
                                    upload.error = error.message || 'Upload failed';
                                }
                            },
                            putReferenceFile(url, file, upload) {
                                return new Promise((resolve, reject) => {
                                    const xhr = new XMLHttpRequest();
                                    xhr.open('PUT', url, true);
                                    xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');

                                    xhr.upload.addEventListener('progress', (event) => {
                                        if (event.lengthComputable) {
                                            upload.progress = Math.round((event.loaded / event.total) * 100);
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
                            dismissReferenceUpload(key) {
                                this.referenceUploads = this.referenceUploads.filter((u) => u.key !== key);
                            },
                            confirmRemoveReferenceFile(file) {
                                this.confirmDeleteFile = file;
                            },
                            async removeReferenceFile(id) {
                                this.confirmDeleteFile = null;
                                this.referenceRemovingIds = [...this.referenceRemovingIds, id];
                                try {
                                    await $wire.deleteReferenceFile(id);
                                    this.referenceFiles = this.referenceFiles.filter((file) => file.id !== id);
                                } finally {
                                    this.referenceRemovingIds = this.referenceRemovingIds.filter((removingId) => removingId !== id);
                                }
                            },
                            async removeAllReferenceFiles() {
                                this.confirmDeleteAllOpen = false;
                                this.deletingAll = true;
                                this.referenceRemovingIds = this.referenceFiles.map((file) => file.id);
                                try {
                                    await $wire.deleteAllReferenceFiles();
                                    this.referenceFiles = [];
                                } finally {
                                    this.deletingAll = false;
                                    this.referenceRemovingIds = [];
                                }
                            },
                        }"
                    >
                        @if ($isOpenBook ?? false)
                            <div class="mb-space-lg space-y-space-sm text-left" wire:key="reference-file-upload">
                                <div class="flex items-center justify-between gap-space-md">
                                    <p class="font-label-sm text-label-sm text-secondary">Reference Files</p>
                                    <button
                                        type="button"
                                        x-show="referenceFiles.length > 0"
                                        x-cloak
                                        :disabled="deletingAll"
                                        @click="confirmDeleteAllOpen = true"
                                        class="text-body-xs text-error hover:underline disabled:opacity-50 inline-flex items-center gap-space-xs"
                                    >
                                        <span x-show="deletingAll" x-cloak class="material-symbols-outlined animate-spin text-[14px]">progress_activity</span>
                                        Delete All
                                    </button>
                                </div>
                                <p class="text-body-xs text-on-surface-variant">Upload any documents, images, or slides you want to reference during this open-book exam.</p>

                                <template x-if="referenceError">
                                    <p class="text-body-xs text-error" x-text="referenceError"></p>
                                </template>

                                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-space-md" x-show="referenceFiles.length > 0" x-cloak>
                                    <template x-for="file in referenceFiles" :key="file.id">
                                        <div class="relative group">
                                            <template x-if="referenceRemovingIds.includes(file.id)">
                                                <div class="flex flex-col items-center gap-space-sm p-space-md rounded-lg animate-pulse">
                                                    <div class="w-10 h-10 rounded-lg bg-surface-container"></div>
                                                    <div class="w-full h-3 rounded bg-surface-container"></div>
                                                </div>
                                            </template>
                                            <template x-if="!referenceRemovingIds.includes(file.id)">
                                                <button
                                                    type="button"
                                                    @click="openReferenceFile(file)"
                                                    class="w-full flex flex-col items-center gap-space-sm p-space-md rounded-lg hover:bg-surface-container transition text-center"
                                                >
                                                    <span class="text-4xl" x-text="file.icon"></span>
                                                    <span class="w-full truncate font-body-xs text-body-xs text-on-surface" x-text="file.title"></span>
                                                </button>
                                            </template>
                                            <button
                                                type="button"
                                                x-show="!referenceRemovingIds.includes(file.id)"
                                                @click="confirmRemoveReferenceFile(file)"
                                                class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-error text-white opacity-0 group-hover:opacity-100 transition flex items-center justify-center"
                                                title="Remove"
                                            >
                                                <span class="material-symbols-outlined text-[14px]">close</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-space-md" x-show="referenceUploads.length > 0" x-cloak>
                                    <template x-for="upload in referenceUploads" :key="upload.key">
                                        <div class="relative flex flex-col items-center gap-space-sm p-space-md rounded-lg text-center">
                                            <span class="text-4xl" :class="upload.error ? '' : 'animate-pulse'" x-text="upload.error ? '⚠️' : '📤'"></span>
                                            <span class="w-full truncate font-body-xs text-body-xs text-on-surface" x-text="upload.name"></span>

                                            <template x-if="upload.error">
                                                <p class="w-full text-body-xs text-error truncate" x-text="upload.error"></p>
                                            </template>
                                            <template x-if="!upload.error">
                                                <div class="w-full space-y-space-xs">
                                                    <div class="w-full h-1.5 bg-surface-container rounded-full overflow-hidden">
                                                        <div class="h-full bg-primary transition-all duration-150" :style="`width: ${upload.progress}%`"></div>
                                                    </div>
                                                    <p class="text-body-xs text-on-surface-variant truncate" x-text="upload.statusText + (upload.statusText === 'Uploading...' ? ' (' + upload.progress + '%)' : '')"></p>
                                                </div>
                                            </template>

                                            <button
                                                type="button"
                                                x-show="upload.error"
                                                x-cloak
                                                @click="dismissReferenceUpload(upload.key)"
                                                class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-error text-white flex items-center justify-center"
                                                title="Dismiss"
                                            >
                                                <span class="material-symbols-outlined text-[14px]">close</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <input
                                    type="file"
                                    multiple
                                    x-ref="referenceFileInput"
                                    accept="{{ $referenceAcceptedExtensions ?? '' }}"
                                    class="hidden"
                                    @change="uploadReferenceFiles($refs.referenceFileInput.files)"
                                />
                                <button
                                    type="button"
                                    @click="$refs.referenceFileInput.click()"
                                    class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition"
                                >
                                    Upload Reference Files
                                </button>
                            </div>

                            <template x-teleport="body">
                                <div
                                    x-show="referenceViewerOpen"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="fixed inset-0 z-[125] flex items-center justify-center bg-black/70 px-gutter"
                                    @click.self="closeReferenceFileViewer()"
                                >
                                    <div class="bg-surface rounded-lg p-space-lg max-w-4xl w-full max-h-[90vh] flex flex-col gap-space-md">
                                        <div class="flex items-center justify-between gap-space-md flex-shrink-0">
                                            <h2 class="font-headline-sm text-headline-sm text-on-surface truncate" x-text="viewingReferenceFile?.title"></h2>
                                            <button
                                                type="button"
                                                @click="closeReferenceFileViewer()"
                                                class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition flex-shrink-0"
                                            >
                                                Close
                                            </button>
                                        </div>

                                        <div
                                            class="bg-surface-container rounded-lg flex-1 overflow-auto"
                                            :class="viewingReferenceFile?.type === 'Markdown' ? '' : 'aspect-video'"
                                        >
                                            <template x-if="viewingReferenceFile?.type === 'Video'">
                                                <video :src="viewingReferenceFile.url" width="100%" height="100%" controls class="w-full h-full">
                                                    Your browser does not support the video tag.
                                                </video>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'PDF'">
                                                <embed :src="viewingReferenceFile.url" type="application/pdf" width="100%" height="100%" class="rounded" />
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Audio'">
                                                <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                                                    <span class="text-5xl">🎵</span>
                                                    <p class="text-body-md text-on-surface" x-text="viewingReferenceFile.title"></p>
                                                    <audio :src="viewingReferenceFile.url" controls class="w-full">
                                                        Your browser does not support the audio element.
                                                    </audio>
                                                </div>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Image'">
                                                <div class="w-full h-full flex items-center justify-center overflow-auto">
                                                    <img :src="viewingReferenceFile.url" :alt="viewingReferenceFile.title" class="max-w-full max-h-full" />
                                                </div>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Interactive'">
                                                <iframe
                                                    :src="viewingReferenceFile.url"
                                                    class="w-full h-full rounded border-0"
                                                    sandbox="allow-scripts allow-same-origin allow-forms"
                                                    :title="viewingReferenceFile.title"
                                                ></iframe>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Presentation'">
                                                <iframe
                                                    :src="'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(viewingReferenceFile.url)"
                                                    width="100%"
                                                    height="100%"
                                                    frameborder="0"
                                                    class="rounded"
                                                ></iframe>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Document' && viewingReferenceFile.extension === 'pdf'">
                                                <embed :src="viewingReferenceFile.url" type="application/pdf" width="100%" height="100%" class="rounded" />
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Document' && ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'].includes(viewingReferenceFile.extension)">
                                                <iframe
                                                    :src="'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(viewingReferenceFile.url)"
                                                    width="100%"
                                                    height="100%"
                                                    frameborder="0"
                                                    class="rounded"
                                                ></iframe>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Document' && ['txt', 'csv', 'md'].includes(viewingReferenceFile.extension)">
                                                <div
                                                    x-data="{ text: null, error: null }"
                                                    x-init="
                                                        fetch(viewingReferenceFile.url)
                                                            .then(response => {
                                                                if (! response.ok) throw new Error('HTTP ' + response.status);
                                                                return response.text();
                                                            })
                                                            .then(content => { text = content; })
                                                            .catch(err => { error = err.message; });
                                                    "
                                                    class="w-full h-full overflow-auto p-space-lg"
                                                >
                                                    <template x-if="! text && ! error">
                                                        <p class="text-center text-on-surface-variant">Loading...</p>
                                                    </template>
                                                    <template x-if="error">
                                                        <p class="text-red-600 font-medium" x-text="'Error loading document: ' + error"></p>
                                                    </template>
                                                    <pre x-show="text" class="whitespace-pre-wrap font-body-sm text-body-sm text-on-surface" x-text="text"></pre>
                                                </div>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Document' && ! ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'csv', 'md'].includes(viewingReferenceFile.extension)">
                                                <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                                                    <span class="text-5xl">📝</span>
                                                    <p class="text-body-md text-on-surface" x-text="viewingReferenceFile.title"></p>
                                                    <a
                                                        :href="viewingReferenceFile.url"
                                                        download
                                                        class="px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition"
                                                    >
                                                        Download Document
                                                    </a>
                                                </div>
                                            </template>

                                            <template x-if="viewingReferenceFile?.type === 'Markdown'">
                                                <div
                                                    x-data="{ html: null, error: null }"
                                                    x-init="
                                                        fetch(viewingReferenceFile.url)
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
                            </template>

                            <template x-teleport="body">
                                <div
                                    x-show="confirmDeleteFile !== null"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 px-gutter"
                                    @click.self="confirmDeleteFile = null"
                                >
                                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Remove reference file?</h2>
                                        <p class="font-body-md text-body-md text-secondary">
                                            Are you sure you want to remove "<span x-text="confirmDeleteFile?.title"></span>"? This cannot be undone.
                                        </p>
                                        <div class="flex items-center justify-end gap-space-md">
                                            <button type="button" @click="confirmDeleteFile = null" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                            <button
                                                type="button"
                                                @click="removeReferenceFile(confirmDeleteFile.id)"
                                                class="px-space-lg py-space-sm bg-error text-white rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-teleport="body">
                                <div
                                    x-show="confirmDeleteAllOpen"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 px-gutter"
                                    @click.self="confirmDeleteAllOpen = false"
                                >
                                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Delete all reference files?</h2>
                                        <p class="font-body-md text-body-md text-secondary">
                                            Are you sure you want to delete all reference files? This cannot be undone.
                                        </p>
                                        <div class="flex items-center justify-end gap-space-md">
                                            <button type="button" @click="confirmDeleteAllOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                            <button
                                                type="button"
                                                @click="removeAllReferenceFiles()"
                                                class="px-space-lg py-space-sm bg-error text-white rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                                            >
                                                Delete All
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        @endif

                        <button
                            type="button"
                            @click="confirmOpen = true"
                            :disabled="navigating"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                        >
                            {{ $latestAttempt ? 'Continue Exam' : 'Start Exam' }}
                        </button>

                        <template x-teleport="body">
                            <div
                                x-show="confirmOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
                                @click.self="confirmOpen = false"
                            >
                                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Continue Exam?' : 'Start Exam?' }}</h2>
                                    <p class="font-body-md text-body-md text-secondary">
                                        This is a proctored exam. You'll first go through pre-flight checks (internet speed, camera, microphone, screen sharing) before the exam begins.
                                    </p>
                                    <div class="flex items-center justify-end gap-space-md">
                                        <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                        <button
                                            type="button"
                                            :disabled="navigating"
                                            @click="navigating = true; Livewire.navigate('{{ route('assessments.final-exam.proctor.preflight', $assessment) }}')"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                                        >
                                            <span x-show="navigating" x-cloak class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                            {{ $latestAttempt ? 'Continue Exam' : 'Start Exam' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @else
                    <div class="p-space-lg bg-surface-container/50 border border-outline-variant rounded-lg">
                        <p class="text-body-sm text-on-surface-variant">This exam isn't ready yet. Please check back later.</p>
                    </div>
                @endif
            @elseif ($canSubmit && $canResubmit)
                <div x-data="{ attemptOpen: false }">
                    <button
                        type="button"
                        @click="attemptOpen = true"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        {{ $latestAttempt ? 'Continue Attempt' : 'Start Attempt' }}
                    </button>

                    <template x-teleport="body">
                        <div
                            x-data="{ confirmOpen: false, answerEmpty: false }"
                            x-show="attemptOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[100] bg-surface flex flex-col"
                        >
                            <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} Answer &mdash; {{ $assessment->title }}</h2>
                                <button type="button" @click="attemptOpen = false" class="p-2 hover:bg-surface-container rounded transition">
                                    <span class="material-symbols-outlined text-on-surface-variant">close</span>
                                </button>
                            </div>

                            <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-2">
                                <!-- Left: Questions -->
                                <div class="overflow-y-auto p-space-lg border-b md:border-b-0 md:border-r border-outline-variant divide-y divide-outline-variant">
                                    @foreach ($assessment->questions as $question)
                                        <div class="space-y-space-md {{ $loop->first ? '' : 'pt-space-lg' }} {{ $loop->last ? '' : 'pb-space-lg' }}">
                                            <p class="text-body-xs text-on-surface-variant mb-space-sm">Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                                            <div class="rte-content prose prose-sm max-w-none text-on-surface">{!! $question->description !!}</div>
                                            @if ($question->files->isNotEmpty())
                                                <div class="mt-space-md space-y-space-xs">
                                                    @foreach ($question->files as $file)
                                                        <div class="flex items-center gap-space-xs text-body-sm text-on-surface-variant">
                                                            <span class="material-symbols-outlined text-[16px]">description</span>
                                                            {{ $file->title }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Right: Answer Input -->
                                <div class="overflow-y-auto p-space-lg">
                                    <form
                                        @submit.prevent="
                                            answerEmpty = ($wire.answerText || '').replace(/<[^>]*>/g, '').trim() === '';
                                            if (!answerEmpty) { confirmOpen = true; }
                                        "
                                        class="space-y-space-md"
                                    >
                                        <label class="block font-label-md text-label-md text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} Answer</label>
                                        <div @input.capture="answerEmpty = false">
                                            <x-rich-text-editor id="answer" wire-model="answerText" :value="$answerText" />
                                            <p x-show="answerEmpty" x-cloak class="text-body-xs text-error mt-space-xs">{{ __('Answer cannot be empty.') }}</p>
                                            @error('answerText') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                        </div>
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="submit"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                                        >
                                            {{ $latestAttempt ? 'Resubmit' : 'Submit' }}
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Submit Confirmation -->
                            <div
                                x-show="confirmOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                                @click.self="confirmOpen = false"
                            >
                                <div
                                    x-show="confirmOpen"
                                    x-transition:enter="transition ease-out duration-200 delay-75"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg"
                                >
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} confirmation</h2>
                                    <p class="font-body-md text-body-md text-secondary">
                                        {{ $latestAttempt
                                            ? 'Are you sure you want to resubmit your answer? This will replace your previous submission.'
                                            : 'Are you sure you want to submit your answer? You will not be able to edit it once graded.' }}
                                    </p>
                                    <div class="flex items-center justify-end gap-space-md">
                                        <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                        <button
                                            type="button"
                                            wire:loading.attr="disabled"
                                            wire:target="submit"
                                            @click="confirmOpen = false; $wire.call('submit').then((ok) => { if (ok) { attemptOpen = false; } })"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                                        >
                                            {{ $latestAttempt ? 'Resubmit' : 'Submit' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
        @endif
    </div>

    @if (!$isStudent && !$isProctored)
        <!-- Question List (teacher view, read-only) -->
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>
            <div class="divide-y divide-outline-variant">
                @foreach ($assessment->questions as $question)
                    <div class="space-y-space-md {{ $loop->first ? '' : 'pt-space-lg' }} {{ $loop->last ? '' : 'pb-space-lg' }}">
                        <p class="text-body-xs text-on-surface-variant mb-space-sm">Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                        <div class="rte-content prose prose-sm max-w-none text-on-surface">{!! $question->description !!}</div>
                        @if ($question->files->isNotEmpty())
                            <div class="mt-space-md space-y-space-xs">
                                @foreach ($question->files as $file)
                                    <div class="flex items-center gap-space-xs text-body-sm text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[16px]">description</span>
                                        {{ $file->title }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Teacher: Manage Exam Questions (proctored exams only) -->
    @if (!$isStudent && $isProctored && $canGrade)
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg flex items-center justify-between gap-space-md">
            <div>
                <p class="font-label-md text-label-md text-on-surface">Exam Questions</p>
                <p class="text-body-sm text-on-surface-variant mt-space-xs">
                    {{ $assessment->quiz && $assessment->quiz->questions->isNotEmpty() ? $assessment->quiz->questions->count().' question(s) configured.' : 'No questions configured yet — students cannot start this exam until questions are added.' }}
                </p>
            </div>
            <a
                href="{{ route('assessments.final-exam.questions.edit', $assessment) }}"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex-shrink-0"
            >
                Manage Questions
            </a>
        </div>
    @endif

    <!-- Teacher: Submissions List -->
    @if (!$isStudent)
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface">Student Submissions</h2>
            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
                @forelse ($studentRows as $row)
                    <div wire:key="student-{{ $row['user']->id }}" class="p-space-lg">
                        <div class="flex items-center justify-between gap-space-md mb-space-md">
                            <div class="flex-1 min-w-0">
                                <p class="font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</p>
                                <p class="text-body-sm text-on-surface-variant mt-1">
                                    @if ($row['attempt'])
                                        Attempt {{ $row['attempt']->attempt_number }} &middot; submitted {{ $row['attempt']->submitted_at_display?->format('M j, Y H:i') }}
                                    @else
                                        Not submitted
                                    @endif
                                </p>
                            </div>

                            <span class="inline-flex items-center px-space-md py-space-xs rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant flex-shrink-0">
                                @if (! $row['attempt'])
                                    Not submitted
                                @elseif ($row['pendingProctorReview'])
                                    Pending Review
                                @elseif ($row['score'])
                                    Score: {{ rtrim(rtrim(number_format($row['score']->score, 2), '0'), '.') }}
                                @else
                                    Ungraded
                                @endif
                            </span>

                            @if ($canGrade && $row['attempt'])
                                <button
                                    type="button"
                                    wire:click="openGrading('{{ $row['user']->id }}')"
                                    class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition flex-shrink-0"
                                >
                                    Grade
                                </button>
                            @endif
                        </div>

                        @if ($isProctored && $row['proctorSession'])
                            <div
                                class="mt-space-md pt-space-md border-t border-outline-variant space-y-space-md"
                                x-data="{
                                    reviewOpen: false,
                                    cameraModalOpen: false,
                                    screenModalOpen: false,
                                    screenshotModalOpen: false,
                                    lightboxUrl: null,
                                    screenshotFilter: 'all',
                                    screenshotGrouped: true,
                                    screenshotSort: 'asc',
                                    screenshotUserId: null,
                                    screenshotItems: [],
                                    screenshotGroups: [],
                                    screenshotEventTypeOptions: [],
                                    screenshotsLoading: false,
                                    screenshotsLoadingMore: false,
                                    screenshotHasMore: false,
                                    init() {
                                        this.$watch('screenshotFilter', () => this.fetchScreenshots());
                                        this.$watch('screenshotSort', () => this.fetchScreenshots());
                                        this.$watch('screenshotGrouped', () => this.fetchScreenshots());
                                    },
                                    openScreenshots(userId) {
                                        this.screenshotModalOpen = true;
                                        this.screenshotUserId = userId;
                                        this.fetchScreenshots();
                                    },
                                    isGroupedView() {
                                        return this.screenshotGrouped && this.screenshotFilter === 'all';
                                    },
                                    async fetchScreenshots() {
                                        if (! this.screenshotUserId) { return; }
                                        this.screenshotsLoading = true;
                                        try {
                                            if (this.isGroupedView()) {
                                                const data = await $wire.loadProctorScreenshotGroups(this.screenshotUserId, this.screenshotSort, 5);
                                                this.screenshotGroups = data.groups.map((group) => ({ ...group, loadingMore: false }));
                                                this.screenshotEventTypeOptions = data.eventTypeOptions;
                                                this.screenshotItems = [];
                                                this.screenshotHasMore = false;
                                            } else {
                                                const eventType = this.screenshotFilter === 'all' ? null : this.screenshotFilter;
                                                const data = await $wire.loadProctorScreenshots(this.screenshotUserId, eventType, this.screenshotSort, 0, 5);
                                                this.screenshotItems = data.items;
                                                this.screenshotEventTypeOptions = data.eventTypeOptions;
                                                this.screenshotHasMore = data.hasMore;
                                                this.screenshotGroups = [];
                                            }
                                        } finally {
                                            this.screenshotsLoading = false;
                                        }
                                    },
                                    async loadMoreScreenshots() {
                                        if (! this.screenshotUserId || ! this.screenshotHasMore || this.screenshotsLoading || this.screenshotsLoadingMore) { return; }
                                        this.screenshotsLoadingMore = true;
                                        try {
                                            const eventType = this.screenshotFilter === 'all' ? null : this.screenshotFilter;
                                            const data = await $wire.loadProctorScreenshots(this.screenshotUserId, eventType, this.screenshotSort, this.screenshotItems.length, 5);
                                            this.screenshotItems = [...this.screenshotItems, ...data.items];
                                            this.screenshotHasMore = data.hasMore;
                                        } finally {
                                            this.screenshotsLoadingMore = false;
                                        }
                                    },
                                    async loadMoreGroupScreenshots(group) {
                                        if (! group.hasMore || group.loadingMore) { return; }
                                        group.loadingMore = true;
                                        try {
                                            const data = await $wire.loadProctorScreenshots(this.screenshotUserId, group.eventType, this.screenshotSort, group.items.length, 5);
                                            group.items = [...group.items, ...data.items];
                                            group.hasMore = data.hasMore;
                                        } finally {
                                            group.loadingMore = false;
                                        }
                                    },
                                    onScreenshotListScroll(e) {
                                        if (this.isGroupedView()) { return; }
                                        const el = e.target;
                                        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 100) {
                                            this.loadMoreScreenshots();
                                        }
                                    },
                                }"
                            >
                                <div class="flex items-center justify-between gap-space-md">
                                    <div class="flex items-center gap-space-sm text-body-sm">
                                        <span class="material-symbols-outlined text-[16px]" :class="{}">shield</span>
                                        <span class="text-on-surface-variant">Proctoring:</span>
                                        <span class="font-medium text-on-surface">{{ str($row['proctorSession']->status->value)->title() }}</span>
                                        <span class="text-on-surface-variant">&middot; {{ $row['proctorSession']->events->count() }} event(s)</span>
                                        @if ($row['proctorSession']->review_decision)
                                            <span class="inline-flex items-center px-space-sm py-0.5 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                                                Review: {{ str($row['proctorSession']->review_decision->value)->replace('_', ' ')->title() }}
                                            </span>
                                        @endif
                                    </div>
                                    <button type="button" @click="reviewOpen = !reviewOpen" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition flex-shrink-0">
                                        Review
                                    </button>
                                </div>

                                <div x-show="reviewOpen" x-cloak class="space-y-space-sm">
                                    @if ($row['cameraRecordings']->isNotEmpty() || $row['screenRecordings']->isNotEmpty() || $row['screenshotsCount'] > 0)
                                        <div class="flex items-center gap-space-sm">
                                            @if ($row['cameraRecordings']->isNotEmpty())
                                                <button type="button" @click="cameraModalOpen = true" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-xs">
                                                    <span class="material-symbols-outlined text-[16px]">videocam</span>
                                                    Preview Camera ({{ $row['cameraRecordings']->count() }})
                                                </button>
                                            @endif

                                            @if ($row['screenRecordings']->isNotEmpty())
                                                <button type="button" @click="screenModalOpen = true" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-xs">
                                                    <span class="material-symbols-outlined text-[16px]">screen_share</span>
                                                    Preview Screen Share ({{ $row['screenRecordings']->count() }})
                                                </button>
                                            @endif

                                            @if ($row['screenshotsCount'] > 0)
                                                <button
                                                    type="button"
                                                    @click="openScreenshots(@js($row['user']->id))"
                                                    class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-xs"
                                                >
                                                    <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                                                    Preview Screenshots ({{ $row['screenshotsCount'] }})
                                                </button>
                                            @endif
                                        </div>

                                        <template x-teleport="body">
                                            <div
                                                x-show="cameraModalOpen"
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                                                @click.self="cameraModalOpen = false"
                                            >
                                                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-2xl w-full space-y-space-md">
                                                    <div class="flex items-center justify-between">
                                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Camera Recording &middot; {{ $row['user']->name }}</h2>
                                                        <button type="button" @click="cameraModalOpen = false" class="text-on-surface-variant hover:text-on-surface">
                                                            <span class="material-symbols-outlined">close</span>
                                                        </button>
                                                    </div>
                                                    <div class="space-y-space-sm max-h-[70vh] overflow-y-auto">
                                                        @foreach ($row['cameraRecordings'] as $recording)
                                                            <video controls preload="none" class="w-full rounded-lg bg-black aspect-video" src="{{ $this->recordingUrl($recording->file_url) }}"></video>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-teleport="body">
                                            <div
                                                x-show="screenModalOpen"
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                                                @click.self="screenModalOpen = false"
                                            >
                                                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-2xl w-full space-y-space-md">
                                                    <div class="flex items-center justify-between">
                                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Screen Share Recording &middot; {{ $row['user']->name }}</h2>
                                                        <button type="button" @click="screenModalOpen = false" class="text-on-surface-variant hover:text-on-surface">
                                                            <span class="material-symbols-outlined">close</span>
                                                        </button>
                                                    </div>
                                                    <div class="space-y-space-sm max-h-[70vh] overflow-y-auto">
                                        @foreach ($row['screenRecordings'] as $recording)
                                                            <video controls preload="none" class="w-full rounded-lg bg-black aspect-video" src="{{ $this->recordingUrl($recording->file_url) }}"></video>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-teleport="body">
                                            <div
                                                x-show="screenshotModalOpen"
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                class="fixed inset-0 z-[110] bg-surface flex flex-col"
                                            >
                                                <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant flex-shrink-0 gap-space-md flex-wrap">
                                                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Event Screenshots &middot; {{ $row['user']->name }}</h2>

                                                    <div class="flex items-center gap-space-sm flex-wrap">
                                                        <select x-model="screenshotFilter" class="px-space-sm py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface bg-surface">
                                                            <option value="all">All event types</option>
                                                            <template x-for="eventType in screenshotEventTypeOptions" :key="eventType">
                                                                <option :value="eventType" x-text="eventType === 'none' ? 'Other' : eventType.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())"></option>
                                                            </template>
                                                        </select>

                                                        <button
                                                            type="button"
                                                            x-show="screenshotFilter !== 'all'"
                                                            x-cloak
                                                            @click="screenshotFilter = 'all'"
                                                            class="inline-flex items-center gap-space-xs px-space-sm py-xs font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface"
                                                        >
                                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                                            Clear filter
                                                        </button>

                                                        <select x-model="screenshotSort" class="px-space-sm py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface bg-surface">
                                                            <option value="asc">Oldest first</option>
                                                            <option value="desc">Newest first</option>
                                                        </select>

                                                        <label class="inline-flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
                                                            <input type="checkbox" x-model="screenshotGrouped" class="w-4 h-4 accent-primary" />
                                                            Group by event
                                                        </label>

                                                        <button type="button" @click="screenshotModalOpen = false" class="text-on-surface-variant hover:text-on-surface">
                                                            <span class="material-symbols-outlined">close</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="flex-1 overflow-y-auto p-space-lg space-y-space-md" @scroll.debounce.150ms="onScreenshotListScroll($event)">
                                                    <template x-if="screenshotsLoading">
                                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                                                            <template x-for="n in 6" :key="n">
                                                                <div class="animate-pulse bg-surface-container rounded-lg aspect-video"></div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    <template x-if="!screenshotsLoading && isGroupedView() && screenshotGroups.length === 0">
                                                        <p class="text-body-sm text-on-surface-variant text-center">No screenshots recorded.</p>
                                                    </template>

                                                    <template x-if="!screenshotsLoading && ! isGroupedView() && screenshotItems.length === 0">
                                                        <p class="text-body-sm text-on-surface-variant text-center">No screenshots recorded.</p>
                                                    </template>

                                                    <template x-if="!screenshotsLoading && isGroupedView() && screenshotGroups.length > 0">
                                                        <div class="space-y-space-md">
                                                            <template x-for="group in screenshotGroups" :key="group.eventType">
                                                                <div class="border border-outline-variant rounded-lg p-space-md space-y-space-sm">
                                                                    <p class="font-label-sm text-label-sm text-on-surface">
                                                                        <span x-text="group.label"></span>
                                                                        <span class="text-body-xs text-on-surface-variant font-normal" x-text="'× ' + group.total"></span>
                                                                    </p>
                                                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                                                                        <template x-for="item in group.items" :key="item.url">
                                                                            <div class="space-y-space-xs">
                                                                                <div class="grid grid-rows-2 gap-space-xs">
                                                                                    <div x-data="{ loaded: false }" class="relative">
                                                                                        <template x-if="item.cameraUrl">
                                                                                            <button
                                                                                                type="button"
                                                                                                @click="lightboxUrl = item.cameraUrl"
                                                                                                class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                                            >
                                                                                                <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                                                <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.cameraUrl" alt="Proctor camera screenshot" />
                                                                                            </button>
                                                                                        </template>
                                                                                        <template x-if="!item.cameraUrl">
                                                                                            <div class="w-full aspect-video rounded-lg bg-surface-container flex items-center justify-center text-body-xs text-on-surface-variant">No camera</div>
                                                                                        </template>
                                                                                    </div>
                                                                                    <div x-data="{ loaded: false }" class="relative">
                                                                                        <button
                                                                                            type="button"
                                                                                            @click="lightboxUrl = item.url"
                                                                                            class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                                        >
                                                                                            <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                                            <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.url" alt="Proctor screen screenshot" />
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                                <p class="text-body-xs text-on-surface-variant text-center" x-text="item.capturedAt"></p>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        x-show="group.hasMore"
                                                                        x-cloak
                                                                        @click="loadMoreGroupScreenshots(group)"
                                                                        :disabled="group.loadingMore"
                                                                        class="text-body-xs text-primary hover:underline disabled:opacity-50 inline-flex items-center gap-space-xs"
                                                                    >
                                                                        <span x-show="group.loadingMore" x-cloak class="material-symbols-outlined animate-spin text-[14px]">progress_activity</span>
                                                                        <span x-text="group.loadingMore ? 'Loading…' : 'Load more'"></span>
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    <template x-if="!screenshotsLoading && ! isGroupedView() && screenshotItems.length > 0">
                                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                                                            <template x-for="item in screenshotItems" :key="item.url">
                                                                <div class="space-y-space-xs">
                                                                    <div class="grid grid-rows-2 gap-space-xs">
                                                                        <div x-data="{ loaded: false }" class="relative">
                                                                            <template x-if="item.cameraUrl">
                                                                                <button
                                                                                    type="button"
                                                                                    @click="lightboxUrl = item.cameraUrl"
                                                                                    class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                                >
                                                                                    <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                                    <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.cameraUrl" alt="Proctor camera screenshot" />
                                                                                </button>
                                                                            </template>
                                                                            <template x-if="!item.cameraUrl">
                                                                                <div class="w-full aspect-video rounded-lg bg-surface-container flex items-center justify-center text-body-xs text-on-surface-variant">No camera</div>
                                                                            </template>
                                                                        </div>
                                                                        <div x-data="{ loaded: false }" class="relative">
                                                                            <button
                                                                                type="button"
                                                                                @click="lightboxUrl = item.url"
                                                                                class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                            >
                                                                                <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                                <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.url" alt="Proctor screen screenshot" />
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <p class="text-body-xs text-on-surface-variant text-center" x-text="item.capturedAt + ' · ' + item.eventTypeLabel"></p>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    <template x-if="screenshotsLoadingMore">
                                                        <div class="flex justify-center pt-space-sm">
                                                            <span class="material-symbols-outlined animate-spin text-on-surface-variant text-[20px]">progress_activity</span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-teleport="body">
                                            <div
                                                x-show="lightboxUrl"
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-150"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-100"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                class="fixed inset-0 z-[120] bg-black/90 flex items-center justify-center"
                                                @click.self="lightboxUrl = null"
                                                @keydown.escape.window="lightboxUrl = null"
                                            >
                                                <button type="button" @click="lightboxUrl = null" class="absolute top-space-lg right-space-lg text-white/80 hover:text-white">
                                                    <span class="material-symbols-outlined text-[32px]">close</span>
                                                </button>
                                                <img :src="lightboxUrl" class="max-w-[95vw] max-h-[95vh] object-contain" alt="Proctor screenshot full view" />
                                            </div>
                                        </template>
                                    @endif

                                    <div class="flex items-end gap-space-sm">
                                        <div class="flex-1">
                                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Decision</label>
                                            <select wire:model="reviewDecision.{{ $row['proctorSession']->id }}" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm">
                                                <option value="no_action" @selected(($row['proctorSession']->review_decision?->value ?? 'no_action') === 'no_action')>No Action</option>
                                                <option value="warning" @selected($row['proctorSession']->review_decision?->value === 'warning')>Warning</option>
                                                <option value="disqualified" @selected($row['proctorSession']->review_decision?->value === 'disqualified')>Disqualified</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Notes</label>
                                            <input type="text" wire:model="reviewNotes.{{ $row['proctorSession']->id }}" value="{{ $row['proctorSession']->review_notes }}" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm" />
                                        </div>
                                        <button type="button" wire:click="reviewProctorSession('{{ $row['proctorSession']->id }}')" class="px-space-md py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity flex-shrink-0">
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($gradingUserId === $row['user']->id)
                            <div class="mt-space-md pt-space-md border-t border-outline-variant space-y-space-md">
                                <div class="rte-content text-body-sm text-on-surface-variant">{!! $row['answer']?->answer_text !!}</div>

                                <form wire:submit="submitGrade" class="space-y-space-md">
                                    <div class="space-y-space-md">
                                        <h4 class="font-label-md text-label-md text-on-surface">Question Scores</h4>
                                        @foreach ($assessment->questions as $question)
                                            <div>
                                                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
                                                    Question {{ $loop->iteration }} ({{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts)
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $question->points }}"
                                                    wire:model="gradeQuestionScores.{{ $question->id }}"
                                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                                />
                                                @error("gradeQuestionScores.{$question->id}") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                            </div>
                                        @endforeach
                                    </div>

                                    <div>
                                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Feedback</label>
                                        <textarea wire:model="gradeFeedback" rows="3" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                                    </div>

                                    <div class="flex gap-space-md">
                                        <button type="button" wire:click="cancelGrading" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                                            Cancel
                                        </button>
                                        <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                            Save Grade
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-space-lg text-center text-body-sm text-on-surface-variant">No students enrolled.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
