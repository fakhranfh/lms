@section('title', $assessment->title)

<div class="space-y-space-lg">
    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    @if ($inProgress)
        <div
            x-data="{
                deadline: @js($deadlineIso),
                remaining: null,
                timer: null,
                submitting: false,
                confirmOpen: false,
                webcamStream: null,
                screenStream: null,
                webcamRecorder: null,
                screenRecorder: null,
                snapshotTimer: null,
                allowedTypes: @js($examType->value),
                tick() {
                    if (! this.deadline) { return; }
                    let diff = Math.floor((new Date(this.deadline) - new Date()) / 1000);
                    this.remaining = Math.max(diff, 0);
                    if (diff <= 0) {
                        clearInterval(this.timer);
                        if (! this.submitting) {
                            this.submitting = true;
                            this.stopRecording();
                            $wire.submitAttempt();
                        }
                    }
                },
                formatted() {
                    if (this.remaining === null) { return ''; }
                    let m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                    let s = (this.remaining % 60).toString().padStart(2, '0');
                    return m + ':' + s;
                },
                logEvent(eventType, severity, metadata = null) {
                    $wire.logProctorEvent(eventType, severity, metadata);
                },
                async captureSnapshot() {
                    if (! this.$refs.webcamPreview || ! this.webcamStream) { return; }
                    const video = this.$refs.webcamPreview;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 320;
                    canvas.height = video.videoHeight || 240;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(async (blob) => {
                        if (! blob) { return; }
                        try {
                            const filename = 'snapshot-' + Date.now() + '.jpg';
                            const { url, key } = await $wire.requestSnapshotUploadUrl(filename, 'Image');
                            await fetch(url, { method: 'PUT', body: blob, headers: { 'Content-Type': 'image/jpeg' } });
                            $wire.recordSnapshotUploaded('webcam', key);
                        } catch (e) {}
                    }, 'image/jpeg', 0.7);
                },
                async startRecording() {
                    try {
                        this.webcamStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                        this.$refs.webcamPreview.srcObject = this.webcamStream;
                    } catch (e) {
                        this.logEvent('no_face_detected', 'high', { reason: 'camera_unavailable' });
                    }
                    try {
                        this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                        this.screenStream.getVideoTracks()[0].addEventListener('ended', () => {
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_stopped' });
                        });
                    } catch (e) {
                        this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_denied' });
                    }
                    this.snapshotTimer = setInterval(() => this.captureSnapshot(), 45000);
                },
                stopRecording() {
                    if (this.snapshotTimer) { clearInterval(this.snapshotTimer); }
                    if (this.webcamStream) { this.webcamStream.getTracks().forEach(t => t.stop()); }
                    if (this.screenStream) { this.screenStream.getTracks().forEach(t => t.stop()); }
                },
            }"
            x-init="
                tick(); timer = setInterval(() => tick(), 1000);
                startRecording();
                document.addEventListener('visibilitychange', () => { if (document.hidden) { logEvent('tab_switch', 'medium'); } });
                window.addEventListener('blur', () => logEvent('window_blur', 'low'));
                document.addEventListener('copy', () => logEvent('copy_paste', 'medium'));
                document.addEventListener('paste', () => logEvent('copy_paste', 'medium'));
                document.addEventListener('contextmenu', (e) => { e.preventDefault(); logEvent('right_click', 'low'); });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'F12' || ((e.ctrlKey || e.metaKey) && e.shiftKey && ['I','J','C'].includes(e.key))) {
                        logEvent('devtools_opened', 'high');
                    }
                });
                document.addEventListener('fullscreenchange', () => { if (! document.fullscreenElement) { logEvent('fullscreen_exit', 'medium'); } });
            "
            x-on:destroy="clearInterval(timer); stopRecording()"
            class="fixed inset-0 z-[100] bg-surface flex flex-col"
        >
            <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant flex-shrink-0">
                <div class="flex items-center gap-space-md">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Attempt {{ $inProgress->attempt_number }} — {{ $assessment->title }}</h2>
                    <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">Proctored</span>
                </div>

                <div class="flex items-center gap-space-lg">
                    <video x-ref="webcamPreview" autoplay muted playsinline class="w-16 h-12 rounded bg-black object-cover"></video>

                    @if ($deadlineIso)
                        <div class="flex items-center gap-space-xs font-label-md text-label-md" :class="remaining !== null && remaining <= 60 ? 'text-error' : 'text-on-surface'">
                            <span class="material-symbols-outlined text-[18px]">timer</span>
                            <span x-text="formatted()"></span>
                        </div>
                    @endif

                    <button
                        type="button"
                        @click="confirmOpen = true"
                        wire:loading.attr="disabled"
                        wire:target="submitAttempt"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                    >
                        <span wire:loading wire:target="submitAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        Submit Exam
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-[220px_1fr]" x-data="{ currentQuestion: 0 }">
                <div class="overflow-y-auto p-space-lg border-b md:border-b-0 md:border-r border-outline-variant">
                    <p class="font-label-sm text-label-sm text-secondary mb-space-md">Questions</p>
                    <div class="grid grid-cols-6 md:grid-cols-4 gap-space-xs">
                        @foreach ($quiz->questions as $question)
                            <button
                                type="button"
                                @click="currentQuestion = {{ $loop->index }}"
                                :class="currentQuestion === {{ $loop->index }} ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface hover:bg-surface-container/70'"
                                class="w-10 h-10 rounded-lg font-label-sm text-label-sm flex items-center justify-center transition"
                            >
                                {{ $loop->iteration }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-y-auto p-space-xl">
                    @foreach ($quiz->questions as $question)
                        <div x-show="currentQuestion === {{ $loop->index }}" x-cloak class="space-y-space-lg max-w-2xl mx-auto">
                            <p class="text-body-sm text-on-surface-variant">Question {{ $loop->iteration }} of {{ $quiz->questions->count() }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                            <div class="rte-content prose prose-lg max-w-none text-on-surface">{!! $question->description !!}</div>

                            @if (in_array($question->question_type->value, ['multiple_choice', 'true_false']))
                                <div class="space-y-space-md">
                                    @foreach ($question->options as $option)
                                        <label class="flex items-center gap-space-md p-space-lg border border-outline rounded-lg cursor-pointer hover:bg-surface-container/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5 transition">
                                            <input type="radio" name="answer-{{ $question->id }}" wire:model="answers.{{ $question->id }}" value="{{ $option->id }}" class="w-5 h-5 accent-primary flex-shrink-0" />
                                            <span class="text-body-lg text-on-surface">{{ $option->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <textarea wire:model="answers.{{ $question->id }}" rows="8" class="w-full px-space-lg py-space-md border border-outline rounded-lg font-body-lg text-body-lg focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between pt-space-lg mt-space-lg border-t border-outline-variant max-w-2xl mx-auto">
                        <button
                            type="button"
                            @click="currentQuestion = Math.max(currentQuestion - 1, 0)"
                            :disabled="currentQuestion === 0"
                            class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition disabled:opacity-50"
                        >
                            Previous
                        </button>

                        <button
                            type="button"
                            x-show="currentQuestion < {{ $quiz->questions->count() - 1 }}"
                            @click="currentQuestion = Math.min(currentQuestion + 1, {{ $quiz->questions->count() - 1 }})"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <div
                x-show="confirmOpen"
                x-cloak
                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                @click.self="confirmOpen = false"
            >
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Submit confirmation</h2>
                    <p class="font-body-md text-body-md text-secondary">
                        Are you sure you want to submit this exam? You will not be able to change your answers afterwards.
                    </p>
                    <div class="flex items-center justify-end gap-space-md">
                        <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                        <button
                            type="button"
                            wire:loading.attr="disabled"
                            wire:target="submitAttempt"
                            @click="confirmOpen = false; submitting = true; clearInterval(timer); stopRecording(); $wire.submitAttempt()"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                        >
                            <span wire:loading wire:target="submitAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @elseif (! $canStart)
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center max-w-2xl mx-auto">
            <p class="text-body-md text-on-surface-variant">This exam is not currently available to start.</p>
            <a href="{{ route('assessments.final-exam.show', $assessment) }}" wire:navigate class="text-primary hover:underline">Back to exam overview</a>
        </div>
    @else
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center max-w-2xl mx-auto space-y-space-lg">
            <p class="text-body-md text-on-surface-variant">Ready to begin? Recording will start immediately.</p>
            <button
                type="button"
                wire:loading.attr="disabled"
                wire:target="startAttempt"
                wire:click="startAttempt"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span wire:loading wire:target="startAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Start Exam
            </button>
        </div>
    @endif
</div>
