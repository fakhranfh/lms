@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="w-full">
        <!-- Breadcrumb -->
        <div class="mb-space-lg">
            <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
                <a href="{{ route('courses.index') }}" class="hover:text-on-surface transition">Courses</a>
                <span>/</span>
                <a href="{{ route('courses.show', $course) }}" class="hover:text-on-surface transition">{{ $course->title }}</a>
                <span>/</span>
                <a href="{{ route('courses.show', $course) }}" class="hover:text-on-surface transition">{{ $module->title }}</a>
                <span>/</span>
                <span class="text-on-surface font-medium">{{ $lesson->title }}</span>
            </nav>
        </div>

        <!-- Header -->
        <div class="mb-space-xl">
            <div class="flex items-start justify-between gap-space-lg">
                <div class="flex-1">
                    <h1 class="font-headline-md text-headline-md text-on-surface">
                        {{ $lesson->title }}
                    </h1>
                    <p class="text-body-md text-on-surface-variant mt-space-sm">
                        {{ $module->title }} • Lesson {{ $currentLessonIndex }} of {{ $totalLessonsInModule }}
                    </p>
                </div>
                @if ($lesson->duration_minutes)
                    <div class="text-body-sm text-on-surface-variant whitespace-nowrap">
                        ⏱ {{ $lesson->duration_minutes }} min
                    </div>
                @endif
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-space-xl">
            <div class="flex items-center justify-between mb-space-sm">
                <span class="text-label-sm text-on-surface-variant font-label-md">Module Progress</span>
                <span class="text-label-sm text-on-surface font-label-md">{{ $completedLessonsInModule }} / {{ $totalLessonsInModule }} lessons</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                <div
                    class="h-full bg-primary transition-all duration-300"
                    style="width: {{ $completionPercentage }}%"
                ></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-space-lg">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Materials Section -->
                @if ($materials->count() > 0)
                    <!-- Material Display -->
                    @if ($selectedMaterial)
                        <div class="mb-space-xl bg-surface-container rounded-lg overflow-hidden">
                            <!-- Material Viewer based on Type -->
                            @switch($selectedMaterial->type->value)
                                @case('Video')
                                    <div class="aspect-video flex items-center justify-center bg-surface">
                                        <video width="100%" height="100%" controls class="w-full h-full">
                                            <source src="{{ $selectedMaterial->file_url }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                    @break

                                @case('PDF')
                                    <div class="aspect-video flex flex-col items-center justify-center bg-surface p-space-lg">
                                        <embed
                                            src="{{ $selectedMaterial->file_url }}"
                                            type="application/pdf"
                                            width="100%"
                                            height="100%"
                                            class="rounded"
                                        />
                                    </div>
                                    @break

                                @case('Audio')
                                    <div class="aspect-video flex flex-col items-center justify-center bg-surface gap-space-md p-space-lg">
                                        <span class="text-5xl">🎵</span>
                                        <p class="text-body-md text-on-surface">{{ $selectedMaterial->title }}</p>
                                        <audio controls class="w-full">
                                            <source src="{{ $selectedMaterial->file_url }}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                    </div>
                                    @break

                                @case('Image')
                                    <div class="aspect-video flex items-center justify-center bg-surface overflow-auto">
                                        <img
                                            src="{{ $selectedMaterial->file_url }}"
                                            alt="{{ $selectedMaterial->title }}"
                                            class="max-w-full max-h-full"
                                        />
                                    </div>
                                    @break

                                @case('Interactive')
                                    <div class="aspect-video flex items-center justify-center bg-surface p-space-lg">
                                        <iframe
                                            src="{{ $selectedMaterial->file_url }}"
                                            class="w-full h-full rounded border-0"
                                            sandbox="allow-scripts allow-same-origin allow-forms"
                                            title="{{ $selectedMaterial->title }}"
                                        ></iframe>
                                    </div>
                                    @break

                                @case('Presentation')
                                    <div class="aspect-video flex flex-col items-center justify-center bg-surface p-space-lg">
                                        <iframe
                                            src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($selectedMaterial->file_url) }}"
                                            width="100%"
                                            height="100%"
                                            frameborder="0"
                                            class="rounded"
                                        ></iframe>
                                    </div>
                                    @break

                                @case('Document')
                                    <div class="aspect-video flex flex-col items-center justify-center bg-surface gap-space-md p-space-lg">
                                        <span class="text-5xl">📝</span>
                                        <p class="text-body-md text-on-surface">{{ $selectedMaterial->title }}</p>
                                        <a
                                            href="{{ $selectedMaterial->file_url }}"
                                            download
                                            class="px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition"
                                        >
                                            Download Document
                                        </a>
                                    </div>
                                    @break

                                @case('Markdown')
                                    <div
                                        wire:key="markdown-{{ $selectedMaterial->id }}"
                                        x-data="{ html: null, error: null }"
                                        x-init="
                                            fetch('{{ $selectedMaterial->file_url }}')
                                                .then(response => {
                                                    if (! response.ok) throw new Error('HTTP ' + response.status);
                                                    return response.text();
                                                })
                                                .then(markdown => { html = window.renderMarkdown(markdown); })
                                                .catch(err => { error = err.message; });
                                        "
                                        class="aspect-video flex items-center justify-center bg-surface p-space-lg overflow-auto"
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
                                    @break
                            @endswitch
                        </div>

                        <!-- Material Actions -->
                        <div class="mb-space-xl flex gap-space-md">
                            @auth
                                @if (! $materialService->isMaterialAccessedBy($selectedMaterial->id, auth()->user()))
                                    <button
                                        wire:click="markMaterialAsRead"
                                        wire:loading.attr="disabled"
                                        wire:target="markMaterialAsRead"
                                        class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex items-center justify-center gap-space-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="markMaterialAsRead">Mark as Read</span>
                                        <span wire:loading wire:target="markMaterialAsRead" class="inline-block animate-spin">⟳</span>
                                        <span wire:loading wire:target="markMaterialAsRead">Marking...</span>
                                    </button>
                                @else
                                    <div class="flex-1 px-space-lg py-space-md bg-primary/10 text-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-space-sm">
                                        <span>✓</span>
                                        <span>Material Accessed</span>
                                    </div>
                                @endif
                            @endauth

                            <a
                                href="{{ $selectedMaterial->file_url }}"
                                download
                                class="px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                            >
                                Download
                            </a>
                        </div>
                    @endif
                @else
                    <!-- No Materials -->
                    <div class="mb-space-xl p-space-lg bg-surface-container rounded-lg border border-outline text-center">
                        <p class="text-body-md text-on-surface-variant">No materials available for this lesson yet.</p>
                    </div>
                @endif

                <!-- Content -->
                @if ($lesson->content)
                    <div class="mb-space-xl bg-surface-container rounded-lg p-space-lg">
                        <h3 class="text-label-lg font-label-md text-on-surface mb-space-md">Lesson Content</h3>
                        <div class="prose prose-sm max-w-none text-on-surface">
                            {!! nl2br(e($lesson->content)) !!}
                        </div>
                    </div>
                @endif

                <!-- Assignments -->
                @if ($assignments->count() > 0)
                    <div class="mb-space-xl bg-surface-container rounded-lg p-space-lg">
                        <h3 class="text-label-lg font-label-md text-on-surface mb-space-md">Assignments</h3>

                        <div class="space-y-space-sm">
                            @foreach ($assignments as $assignment)
                                @php $submission = $assignment->submissions->first(); @endphp
                                <div class="flex items-center justify-between p-space-md bg-surface rounded-lg border border-outline">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-body-md text-on-surface font-medium truncate">{{ $assignment->title }}</p>
                                        @if ($submission)
                                            <p class="text-body-sm text-on-surface-variant">
                                                Status: {{ $submission->status->label() }}
                                                @if ($submission->isGraded())
                                                    &middot; Score: {{ $submission->getDisplayScore() }} / {{ $assignment->max_score }}
                                                @endif
                                            </p>
                                        @else
                                            <p class="text-body-sm text-on-surface-variant">Not submitted yet</p>
                                        @endif
                                    </div>
                                    @auth
                                        <div class="flex-shrink-0 ml-space-md flex items-center gap-space-sm">
                                            @if ($submission)
                                                <a href="{{ route('submissions.show', $submission) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                                                    View Submission
                                                </a>
                                            @endif

                                            @if (! $submission || ($assignment->allow_multiple_submissions && $submission->status->isTerminal()))
                                                <a href="{{ route('submissions.create', ['lesson' => $lesson, 'assignment' => $assignment]) }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                                    {{ $submission ? 'Submit Another Attempt' : 'Start Assignment' }}
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <a href="{{ route('login') }}" class="flex-shrink-0 ml-space-md px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                                            Login to Submit
                                        </a>
                                    @endauth
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Mark Complete Button -->
                @auth
                    <div class="mb-space-xl">
                        @if (!$isCompleted)
                            <button
                                wire:click="markComplete"
                                wire:loading.attr="disabled"
                                class="w-full px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex items-center justify-center gap-space-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove>✓</span>
                                <span wire:loading class="inline-block animate-spin">⟳</span>
                                <span wire:loading.remove>Mark as Complete</span>
                                <span wire:loading>Marking...</span>
                            </button>
                        @else
                            <div class="w-full px-space-lg py-space-md bg-primary/10 text-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-space-sm">
                                <span>✓</span>
                                <span>Lesson Complete</span>
                            </div>
                        @endif
                    </div>
                @endauth

                <!-- Navigation -->
                <div class="flex gap-space-md">
                    @if ($previousLesson)
                        <a
                            href="{{ route('lessons.show', $previousLesson) }}"
                            class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                        >
                            ← Previous Lesson
                        </a>
                    @else
                        <div class="flex-1"></div>
                    @endif

                    @if ($nextLesson)
                        <a
                            href="{{ route('lessons.show', $nextLesson) }}"
                            class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md text-center hover:opacity-90 transition-opacity"
                        >
                            Next Lesson →
                        </a>
                    @else
                        <a
                            href="{{ route('courses.show', $course) }}"
                            class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md text-center hover:opacity-90 transition-opacity"
                        >
                            Back to Course
                        </a>
                    @endif
                </div>
            </div>

            <!-- Sidebar: Materials and Course Outline -->
            <div class="lg:col-span-1 space-y-space-lg">
                <!-- Materials List -->
                @if ($materials->count() > 0)
                    <div class="bg-surface-container rounded-lg p-space-lg sticky top-space-lg">
                        <h3 class="font-label-lg text-label-lg text-on-surface mb-space-md">
                            Materials
                        </h3>

                        <!-- Material Progress -->
                        <div class="mb-space-md">
                            <div class="flex items-center justify-between mb-space-sm">
                                <span class="text-label-sm text-on-surface-variant">Progress</span>
                                <span class="text-label-sm font-label-md">{{ $accessedMaterialCount }} / {{ $totalMaterialCount }}</span>
                            </div>
                            <div class="w-full h-2 bg-surface rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-primary transition-all duration-300"
                                    style="width: {{ $materialProgress }}%"
                                ></div>
                            </div>
                        </div>

                        <!-- Material Items -->
                        <div class="space-y-space-xs max-h-96 overflow-y-auto">
                            @foreach ($materials as $material)
                                <button
                                    wire:click="selectMaterial('{{ $material->id }}')"
                                    class="w-full text-left px-space-md py-space-sm rounded-lg transition @if ($selectedMaterial?->id === $material->id) bg-primary/10 text-primary font-medium @else text-on-surface hover:bg-surface @endif"
                                >
                                    <div class="flex items-start gap-space-sm">
                                        <span class="flex-shrink-0 pt-space-xs">
                                            @if (auth()->check() && $materialService->isMaterialAccessedBy($material->id, auth()->user()))
                                                ✓
                                            @else
                                                {{ $this->getMaterialIcon($material->type) }}
                                            @endif
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-body-sm truncate">{{ $material->title }}</p>
                                            <p class="text-body-xs text-on-surface-variant">{{ $material->type->value }}</p>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Course Outline -->
                <div class="bg-surface-container rounded-lg p-space-lg sticky top-space-lg">
                    <h3 class="font-label-lg text-label-lg text-on-surface mb-space-md">
                        {{ $course->title }}
                    </h3>

                    <div class="space-y-space-md max-h-96 overflow-y-auto">
                        @foreach ($course->modules()->where('is_published', true)->orderBy('order')->get() as $mod)
                            <div class="space-y-space-xs">
                                <div class="px-space-md py-space-sm rounded-lg bg-surface text-on-surface-variant">
                                    <p class="text-label-sm font-label-md">{{ $mod->title }}</p>
                                </div>

                                <div class="space-y-space-xs pl-space-md">
                                    @foreach ($mod->lessons()->where('is_published', true)->orderBy('order')->get() as $item)
                                        <a
                                            href="{{ route('lessons.show', $item) }}"
                                            class="block px-space-md py-space-sm rounded-lg transition @if ($item->id === $lesson->id) bg-primary/10 text-primary font-medium @else text-on-surface hover:bg-surface @endif"
                                        >
                                            <div class="flex items-start gap-space-sm">
                                                <span class="w-4 h-5 flex-shrink-0 flex items-center justify-center text-body-sm font-medium">
                                                    @if (auth()->check() && app(\App\Services\UserLessonService::class)->isCompletedBy($item->id, auth()->user()))
                                                        ✓
                                                    @else
                                                        •
                                                    @endif
                                                </span>
                                                <span class="text-body-sm break-words">{{ $item->title }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        if (! window.renderMarkdown) {
            window.renderMarkdown = function(markdown) {
                let html = escapeHtml(markdown);

                // Code blocks FIRST (before other replacements)
                html = html.replace(/```[\s\S]*?```/g, function (match) {
                    const code = match.slice(3, -3).trim();

                    return '\n<pre class="bg-surface-container p-space-lg rounded overflow-x-auto my-space-lg"><code class="font-mono text-body-sm text-on-surface">' +
                        code + '</code></pre>\n';
                });

                // Inline code (must be before other formatting)
                html = html.replace(/`([^`\n]+)`/g, '<code class="bg-surface-container px-space-xs py-space-xxs rounded font-mono text-body-sm text-on-surface">$1</code>');

                // Headers
                html = html.replace(/^### (.*?)$/gm, '<h3 class="text-headline-sm font-headline-sm text-on-surface mt-space-lg mb-space-md">$1</h3>');
                html = html.replace(/^## (.*?)$/gm, '<h2 class="text-headline-md font-headline-md text-on-surface mt-space-lg mb-space-md">$1</h2>');
                html = html.replace(/^# (.*?)$/gm, '<h1 class="text-headline-lg font-headline-lg text-on-surface mt-space-lg mb-space-md">$1</h1>');

                // Bold (must be before italic)
                html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-on-surface">$1</strong>');
                html = html.replace(/__(.+?)__/g, '<strong class="font-semibold text-on-surface">$1</strong>');

                // Italic
                html = html.replace(/\*([^*\n]+)\*/g, '<em class="italic text-on-surface">$1</em>');
                html = html.replace(/_([^_\n]+)_/g, '<em class="italic text-on-surface">$1</em>');

                // Links
                html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-primary hover:underline transition" target="_blank">$1</a>');

                // Blockquotes
                html = html.replace(/^&gt; (.*?)$/gm, '<blockquote class="border-l-4 border-primary pl-space-md italic text-on-surface-variant my-space-md">$1</blockquote>');

                // Unordered lists
                html = html.replace(/^- (.*?)$/gm, '<li class="ml-space-md text-on-surface">$1</li>');
                html = html.replace(/(<li[^>]*>.*?<\/li>)/s, '<ul class="list-disc space-y-space-xs mb-space-md">$1</ul>');

                // Paragraphs
                html = html.split('\n\n').map(function (para) {
                    para = para.trim();
                    if (! para) return '';
                    if (para.startsWith('<h') || para.startsWith('<pre') ||
                        para.startsWith('<ul') || para.startsWith('<blockquote')) {
                        return para;
                    }

                    return '<p class="mb-space-md text-on-surface">' + para + '</p>';
                }).join('');

                return html;
            };

            var escapeHtml = function (text) {
                const div = document.createElement('div');
                div.textContent = text;

                return div.innerHTML;
            };
        }
    </script>
@endpush
