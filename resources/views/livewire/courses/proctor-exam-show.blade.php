@section('title', $assessment->title)

<div class="space-y-space-lg" x-data x-init="$el.closest('main')?.scrollTo(0, 0)">
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
                screenShareError: null,
                webcamRecorder: null,
                screenRecorder: null,
                snapshotTimer: null,
                allowedTypes: @js($examType->value),
                currentQuestion: 0,
                tick() {
                    if (! this.deadline) { return; }
                    let diff = Math.floor((new Date(this.deadline) - new Date()) / 1000);
                    this.remaining = Math.max(diff, 0);
                    if (diff <= 0) {
                        this.finishSubmit();
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
                startMediaRecorder(stream, prefix) {
                    if (! stream) { return null; }
                    const mimeType = ['video/webm;codecs=vp8,opus', 'video/webm']
                        .find((type) => window.MediaRecorder && MediaRecorder.isTypeSupported(type));
                    if (! mimeType) { return null; }
                    try {
                        const recorder = new MediaRecorder(stream, { mimeType });
                        recorder.ondataavailable = (e) => {
                            if (e.data && e.data.size > 0) {
                                this.uploadRecordingChunk(e.data, prefix);
                            }
                        };
                        recorder.start(60000);
                        return recorder;
                    } catch (e) {
                        return null;
                    }
                },
                async uploadRecordingChunk(blob, prefix) {
                    try {
                        const filename = prefix + '-' + Date.now() + '.webm';
                        const { url, key } = await $wire.requestSnapshotUploadUrl(filename, 'Video');
                        await fetch(url, { method: 'PUT', body: blob, headers: { 'Content-Type': 'video/webm' } });
                        $wire.recordSnapshotUploaded('recording', key);
                    } catch (e) {}
                },
                async captureSnapshot() {
                    if (this.submitting || ! this.$refs.webcamPreview || ! this.webcamStream) { return; }
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
                    const pending = window.__proctorPendingStreams;
                    window.__proctorPendingStreams = null;

                    if (pending && pending.webcam.getVideoTracks()[0]?.readyState === 'live') {
                        this.webcamStream = pending.webcam;
                    } else {
                        try {
                            this.webcamStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                        } catch (e) {
                            this.logEvent('no_face_detected', 'high', { reason: 'camera_unavailable' });
                        }
                    }
                    this.webcamRecorder = this.startMediaRecorder(this.webcamStream, 'webcam-recording');

                    if (pending && pending.screen.getVideoTracks()[0]?.readyState === 'live') {
                        this.screenStream = pending.screen;
                        this.screenStream.getVideoTracks()[0].addEventListener('ended', () => {
                            this.screenStream = null;
                            this.screenShareError = 'Screen sharing was stopped. Click Share Screen to resume.';
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_stopped' });
                        });
                        this.screenRecorder = this.startMediaRecorder(this.screenStream, 'screen-recording');
                    } else {
                        await this.shareScreen();
                    }

                    this.snapshotTimer = setInterval(() => this.captureSnapshot(), 45000);
                },
                async shareScreen() {
                    this.screenShareError = null;
                    try {
                        const stream = await navigator.mediaDevices.getDisplayMedia({ video: { displaySurface: 'monitor' } });
                        const track = stream.getVideoTracks()[0];
                        if (track.getSettings().displaySurface !== 'monitor') {
                            track.stop();
                            this.screenShareError = 'You must share your entire screen, not a window or tab. Click Share Screen and choose &quot;Entire Screen&quot;.';
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_not_full_screen' });
                            return;
                        }
                        this.screenStream = stream;
                        track.addEventListener('ended', () => {
                            this.screenStream = null;
                            this.screenShareError = 'Screen sharing was stopped. Click Share Screen to resume.';
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_stopped' });
                        });
                        if (this.screenRecorder && this.screenRecorder.state !== 'inactive') {
                            this.screenRecorder.stop();
                        }
                        this.screenRecorder = this.startMediaRecorder(this.screenStream, 'screen-recording');
                    } catch (e) {
                        this.screenShareError = 'Screen sharing was denied or unavailable. Click Share Screen and allow sharing your entire screen.';
                        this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_denied' });
                    }
                },
                async stopRecording() {
                    try {
                        if (this.snapshotTimer) { clearInterval(this.snapshotTimer); }
                        const stops = [];
                        if (this.webcamRecorder && this.webcamRecorder.state !== 'inactive') {
                            stops.push(new Promise((resolve) => {
                                this.webcamRecorder.addEventListener('stop', resolve, { once: true });
                                this.webcamRecorder.stop();
                            }));
                        }
                        if (this.screenRecorder && this.screenRecorder.state !== 'inactive') {
                            stops.push(new Promise((resolve) => {
                                this.screenRecorder.addEventListener('stop', resolve, { once: true });
                                this.screenRecorder.stop();
                            }));
                        }
                        await Promise.all(stops);
                        if (this.webcamStream) { this.webcamStream.getTracks().forEach(t => t.stop()); }
                        if (this.screenStream) { this.screenStream.getTracks().forEach(t => t.stop()); }
                    } catch (e) {}
                },
                async finishSubmit() {
                    if (this.submitting) { return; }
                    this.submitting = true;
                    clearInterval(this.timer);
                    await this.stopRecording();
                    $wire.submitAttempt();
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

            <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-[220px_1fr]">
                <div class="overflow-y-auto p-space-lg border-b md:border-b-0 md:border-r border-outline-variant space-y-space-lg">
                    <div>
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

                    <div
                        :class="screenStream ? 'space-y-space-sm' : 'fixed inset-0 z-20 bg-surface flex flex-col items-center justify-center gap-space-lg p-space-xl'"
                    >
                        <template x-if="!screenStream">
                            <div class="text-center space-y-space-sm">
                                <span class="material-symbols-outlined text-error text-[40px]">screen_share</span>
                                <h3 class="font-headline-sm text-headline-sm text-on-surface">Full-Screen Sharing Required</h3>
                            </div>
                        </template>

                        <div :class="screenStream ? 'space-y-space-sm' : 'flex flex-col sm:flex-row gap-space-lg'">
                            <div class="space-y-space-sm" :class="screenStream ? '' : 'w-48'">
                                <p class="font-label-sm text-label-sm text-secondary">Camera</p>
                                <div class="relative rounded-lg overflow-hidden bg-black aspect-video">
                                    <video x-ref="webcamPreview" x-effect="$el.srcObject = webcamStream" autoplay muted playsinline class="w-full h-full object-cover"></video>
                                    <span x-show="!webcamStream" x-cloak class="absolute inset-0 flex items-center justify-center text-body-xs text-white/70">
                                        Camera unavailable
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-space-sm" :class="screenStream ? '' : 'w-48'">
                                <p class="font-label-sm text-label-sm text-secondary">Screen Share</p>
                                <div class="relative rounded-lg overflow-hidden bg-black aspect-video">
                                    <video x-ref="screenPreview" x-show="screenStream" x-cloak x-effect="$el.srcObject = screenStream" autoplay muted playsinline class="w-full h-full object-cover"></video>
                                    <span x-show="!screenStream" x-cloak class="absolute inset-0 flex items-center justify-center text-body-xs text-white/70">
                                        Screen share unavailable
                                    </span>
                                </div>
                            </div>
                        </div>

                        <template x-if="!screenStream">
                            <div class="text-center space-y-space-md max-w-sm">
                                <p class="text-body-sm text-on-surface-variant" x-text="screenShareError || 'The exam is hidden until you share your entire screen again.'"></p>
                                <button
                                    type="button"
                                    @click="shareScreen()"
                                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                                >
                                    Share Screen
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="overflow-y-auto p-space-xl relative">
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
                                @click="confirmOpen = false; finishSubmit()"
                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                            >
                                <span wire:loading wire:target="submitAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @elseif (! $canStart)
        <div class="fixed top-0 inset-x-0 flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant bg-surface z-10">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $assessment->title }}</h2>
            <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">Proctored</span>
        </div>
        <div class="pt-24">
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center max-w-2xl mx-auto">
            <p class="text-body-md text-on-surface-variant">This exam is not currently available to start.</p>
            <a href="{{ route('assessments.final-exam.show', $assessment) }}" wire:navigate class="text-primary hover:underline">Back to exam overview</a>
        </div>
        </div>
    @else
        <div class="fixed top-0 inset-x-0 flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant bg-surface z-10">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $assessment->title }}</h2>
            <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">Proctored</span>
        </div>
        <div class="pt-24">
        <div
            class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center max-w-2xl mx-auto space-y-space-lg"
            x-data="{
                confirmOpen: false,
                cameraStream: null,
                cameraError: null,
                screenStream: null,
                screenError: null,
                get ready() { return !!this.cameraStream && !!this.screenStream; },
                async checkCamera() {
                    this.cameraError = null;
                    try {
                        this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                        this.$nextTick(() => { if (this.$refs.cameraPreview) { this.$refs.cameraPreview.srcObject = this.cameraStream; } });
                    } catch (e) {
                        this.cameraError = e.name === 'NotAllowedError'
                            ? 'Camera access was denied. Please allow camera permission and try again.'
                            : (e.name === 'NotFoundError' ? 'No camera was found on this device.' : `Camera access was denied or unavailable (${e.name || 'unknown error'}).`);
                    }
                },
                async checkScreen() {
                    this.screenError = null;
                    try {
                        const stream = await navigator.mediaDevices.getDisplayMedia({ video: { displaySurface: 'monitor' } });
                        const track = stream.getVideoTracks()[0];
                        if (track.getSettings().displaySurface !== 'monitor') {
                            track.stop();
                            this.screenError = 'You must share your entire screen, not a window or tab. Click Share Screen and choose &quot;Entire Screen&quot;.';
                            return;
                        }
                        this.screenStream = stream;
                        this.$nextTick(() => { if (this.$refs.screenPreview) { this.$refs.screenPreview.srcObject = this.screenStream; } });
                        track.addEventListener('ended', () => {
                            this.screenStream = null;
                            this.screenError = 'Screen sharing was stopped. Click Share Screen to try again.';
                        });
                    } catch (e) {
                        this.screenError = e.name === 'NotAllowedError'
                            ? 'Screen sharing was denied. Please allow sharing your entire screen and try again.'
                            : `Screen sharing was denied or unavailable (${e.name || 'unknown error'}).`;
                    }
                },
                startExam() {
                    if (! this.ready) { return; }
                    window.__proctorPendingStreams = { webcam: this.cameraStream, screen: this.screenStream };
                    this.confirmOpen = false;
                    $wire.startAttempt();
                },
            }"
            x-on:destroy="if (! ready) { if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); } if (screenStream) { screenStream.getTracks().forEach(t => t.stop()); } }"
        >
            <p class="text-body-md text-on-surface-variant">Camera and full-screen sharing must pass before the exam can begin.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-lg text-left">
                <div class="space-y-space-sm">
                    <p class="font-label-sm text-label-sm text-secondary">Camera</p>
                    <div class="relative rounded-lg overflow-hidden bg-black aspect-video">
                        <video x-ref="cameraPreview" x-show="cameraStream" x-cloak autoplay muted playsinline class="w-full h-full object-cover"></video>
                        <span x-show="!cameraStream" x-cloak class="absolute inset-0 flex items-center justify-center text-body-xs text-white/70">
                            Camera unavailable
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="checkCamera()"
                        class="w-full px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition"
                    >
                        <span x-show="!cameraStream">Enable Camera</span>
                        <span x-show="cameraStream" x-cloak>Retry</span>
                    </button>
                    <p x-show="cameraError" x-cloak class="text-body-xs text-error" x-text="cameraError"></p>
                </div>

                <div class="space-y-space-sm">
                    <p class="font-label-sm text-label-sm text-secondary">Screen Share</p>
                    <div class="relative rounded-lg overflow-hidden bg-black aspect-video">
                        <video x-ref="screenPreview" x-show="screenStream" x-cloak autoplay muted playsinline class="w-full h-full object-cover"></video>
                        <span x-show="!screenStream" x-cloak class="absolute inset-0 flex items-center justify-center text-body-xs text-white/70">
                            Screen share unavailable
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="checkScreen()"
                        class="w-full px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition"
                    >
                        <span x-show="!screenStream">Share Screen</span>
                        <span x-show="screenStream" x-cloak>Retry</span>
                    </button>
                    <p x-show="screenError" x-cloak class="text-body-xs text-error" x-text="screenError"></p>
                </div>
            </div>

            <button
                type="button"
                @click="confirmOpen = true"
                :disabled="!ready"
                wire:loading.attr="disabled"
                wire:target="startAttempt"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span wire:loading wire:target="startAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Start Exam
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
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg text-left">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Start Exam?</h2>
                        <p class="font-body-md text-body-md text-secondary">
                            Once started, your webcam and screen will be recorded for the duration of the exam. You will not be able to pause once you begin.
                        </p>
                        <div class="flex items-center justify-end gap-space-md">
                            <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                            <button
                                type="button"
                                wire:loading.attr="disabled"
                                wire:target="startAttempt"
                                @click="startExam()"
                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                            >
                                <span wire:loading wire:target="startAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                Start Exam
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        </div>
    @endif
</div>
