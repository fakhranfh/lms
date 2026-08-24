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
                pendingUploads: [],
                allowedTypes: @js($examType->value),
                examMaterials: @js($examMaterials),
                examSessions: @js($examSessions),
                materialListOpen: false,
                materialSessionFilter: '',
                materialSearch: '',
                materialViewerOpen: false,
                viewingMaterial: null,
                get filteredMaterials() {
                    const search = this.materialSearch.trim().toLowerCase();

                    return this.examMaterials.filter((material) => {
                        const matchesSession = ! this.materialSessionFilter || material.sessionId === this.materialSessionFilter;
                        const matchesSearch = ! search || material.title.toLowerCase().includes(search);

                        return matchesSession && matchesSearch;
                    });
                },
                currentQuestion: 0,
                eventAbortController: null,
                readingDetector: null,
                noFaceSuspected: false,
                facingDownSuspected: false,
                disqualifying: false,
                mediaPromptActive: false,
                violationCounts: {},
                violationWarningOpen: false,
                violationWarningMessage: '',
                violationWarningEventType: null,
                lastViolationAt: {},
                violationsDisabled: false,
                needsFullscreenResume: false,
                resumeFullscreen() {
                    document.documentElement.requestFullscreen?.().then(() => {
                        this.needsFullscreenResume = false;
                    }).catch(() => {
                        this.needsFullscreenResume = true;
                    });
                },
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
                async logEvent(eventType, severity, metadata = null) {
                    if (this.submitting) { return; }
                    const eventId = await $wire.logProctorEvent(eventType, severity, metadata);
                    // Wait a beat after the event before capturing, so the
                    // screenshot reflects what's on screen once the violation
                    // has actually happened (e.g. after a tab switch resolves)
                    // rather than the transitional frame at the trigger instant.
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    const screenCapture = this.captureVideoSnapshot(this.$refs.screenPreview, this.screenStream, 'screen');
                    const webcamCapture = this.captureVideoSnapshot(this.$refs.webcamPreview, this.webcamStream, 'webcam');
                    this.uploadSnapshot(screenCapture, 'screen', eventId);
                    this.uploadSnapshot(webcamCapture, 'webcam', eventId);
                },
                captureVideoSnapshot(video, stream, type) {
                    if (! video) { console.warn('Proctor ' + type + ' snapshot skipped: no <video> ref'); return null; }
                    if (! stream) { console.warn('Proctor ' + type + ' snapshot skipped: no stream'); return null; }
                    if (video.readyState < 2) { console.warn('Proctor ' + type + ' snapshot skipped: video not ready (readyState=' + video.readyState + ')'); return null; }
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth || 320;
                        canvas.height = video.videoHeight || 240;
                        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                        return { canvas, capturedAt: new Date().toISOString() };
                    } catch (e) {
                        console.error('Proctor ' + type + ' snapshot capture failed', e);

                        return null;
                    }
                },
                uploadSnapshot(capture, type, triggeredByEventId = null) {
                    if (! capture) { return; }
                    const upload = new Promise((resolve) => {
                        capture.canvas.toBlob(async (blob) => {
                            if (! blob) { console.warn('Proctor ' + type + ' snapshot skipped: canvas.toBlob returned null (tainted canvas?)'); resolve(); return; }
                            try {
                                const filename = type + '-' + Date.now() + '.jpg';
                                const { url, key } = await $wire.requestSnapshotUploadUrl(filename, 'Image');
                                const putResponse = await fetch(url, { method: 'PUT', body: blob, headers: { 'Content-Type': 'image/jpeg' } });
                                if (! putResponse.ok) {
                                    throw new Error('R2 PUT failed with status ' + putResponse.status);
                                }
                                await $wire.recordSnapshotUploaded(type, key, triggeredByEventId, capture.capturedAt);
                                console.log('Proctor ' + type + ' snapshot saved:', key);
                            } catch (e) {
                                console.error('Proctor ' + type + ' screenshot upload failed', e);
                            }
                            resolve();
                        }, 'image/jpeg', 0.7);
                    });
                    this.pendingUploads.push(upload);
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
                                this.pendingUploads.push(this.uploadRecordingChunk(e.data, prefix));
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
                        await $wire.recordSnapshotUploaded('recording', key);
                    } catch (e) {
                        console.error('Proctor recording upload failed', e);
                    }
                },
                async startRecording() {
                    this.mediaPromptActive = true;
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
                    this.startReadingDetector();

                    if (pending && pending.screen.getVideoTracks()[0]?.readyState === 'live') {
                        this.screenStream = pending.screen;
                        this.screenStream.getVideoTracks()[0].addEventListener('ended', () => {
                            this.screenStream = null;
                            this.screenShareError = 'Screen sharing was stopped. Click Share Screen to resume.';
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_stopped' });
                        });
                        this.screenRecorder = this.startMediaRecorder(this.screenStream, 'screen-recording');
                        this.mediaPromptActive = false;
                    } else {
                        await this.shareScreen();
                    }

                },
                async startReadingDetector() {
                    if (! this.webcamStream || ! window.createReadingDetector) { return; }
                    this.readingDetector = window.createReadingDetector({
                        onNoFaceSuspectedChange: (suspected) => {
                            this.noFaceSuspected = suspected;
                        },
                        onFacingDownSuspectedChange: (suspected) => {
                            this.facingDownSuspected = suspected;
                            if (suspected) {
                                this.handleDisqualification('reading_suspected', 'sustained_facing_down');
                            }
                        },
                    });
                    try {
                        await this.readingDetector.start(this.$refs.webcamPreview);
                    } catch (e) {
                        console.error('Reading detector failed to start', e);
                    }
                },
                violationMessages: {
                    tab_switch: 'You switched away from this exam tab. This has been logged.',
                    window_blur: 'You switched to another window. This has been logged.',
                    copy_paste: 'Copy/paste is not allowed during this exam. This has been logged.',
                    right_click: 'Right-click is not allowed during this exam. This has been logged.',
                    devtools_opened: 'Opening developer tools is not allowed during this exam. This has been logged.',
                    fullscreen_exit: 'You exited full-screen mode. This has been logged.',
                    navigation_attempt: 'Navigating away from this exam is not allowed. This has been logged.',
                },
                handleViolation(eventType, severity, metadata = null) {
                    if (this.violationsDisabled) { return; }
                    if (this.submitting || this.disqualifying) { return; }
                    if (this.mediaPromptActive && ['tab_switch', 'window_blur', 'fullscreen_exit', 'navigation_attempt'].includes(eventType)) { return; }
                    const now = Date.now();
                    const last = this.lastViolationAt[eventType] || 0;
                    if (now - last < 1000) { return; }
                    this.lastViolationAt[eventType] = now;
                    const count = (this.violationCounts[eventType] || 0) + 1;
                    this.violationCounts[eventType] = count;
                    this.logEvent(eventType, severity, metadata);
                    if (count === 1) {
                        this.violationWarningEventType = eventType;
                        this.violationWarningMessage = (this.violationMessages[eventType] || 'A violation was detected.')
                            + ' If it happens again, you will be automatically disqualified.';
                        this.violationWarningOpen = true;
                    } else {
                        this.handleDisqualification(eventType, 'repeated_' + eventType);
                    }
                },
                async handleDisqualification(eventType, reason) {
                    if (this.submitting || this.disqualifying) { return; }
                    this.disqualifying = true;
                    this.submitting = true;
                    this.materialListOpen = false;
                    this.materialViewerOpen = false;
                    clearInterval(this.timer);
                    this.eventAbortController?.abort();
                    this.readingDetector?.stop();
                    const eventId = await $wire.logProctorEvent(eventType, 'high', { reason });
                    await $wire.beginDisqualification(eventType);
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    const screenCapture = this.captureVideoSnapshot(this.$refs.screenPreview, this.screenStream, 'screen');
                    const webcamCapture = this.captureVideoSnapshot(this.$refs.webcamPreview, this.webcamStream, 'webcam');
                    this.uploadSnapshot(screenCapture, 'screen', eventId);
                    this.uploadSnapshot(webcamCapture, 'webcam', eventId);
                    this.exitFullscreen();
                    await this.stopRecording();
                    window.location.reload();
                },
                async shareScreen() {
                    this.mediaPromptActive = true;
                    this.screenShareError = null;
                    try {
                        const stream = await navigator.mediaDevices.getDisplayMedia({ video: { displaySurface: 'monitor' }, audio: true });
                        const track = stream.getVideoTracks()[0];
                        if (track.getSettings().displaySurface !== 'monitor') {
                            stream.getTracks().forEach(t => t.stop());
                            this.screenShareError = 'You must share your entire screen, not a window or tab. Click Share Screen and choose &quot;Entire Screen&quot;.';
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_not_full_screen' });
                            return;
                        }
                        if (stream.getAudioTracks().length === 0) {
                            stream.getTracks().forEach(t => t.stop());
                            this.screenShareError = 'You must also share audio. Click Share Screen and enable the &quot;Share audio&quot; (or &quot;Share tab audio&quot;) option.';
                            this.logEvent('fullscreen_exit', 'high', { reason: 'screen_share_no_audio' });
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
                    } finally {
                        setTimeout(() => { this.mediaPromptActive = false; }, 1000);
                    }
                },
                async stopRecording() {
                    try {
                        this.readingDetector?.stop();
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
                        await Promise.all(this.pendingUploads);
                        this.pendingUploads = [];
                        if (this.webcamStream) { this.webcamStream.getTracks().forEach(t => t.stop()); }
                        if (this.screenStream) { this.screenStream.getTracks().forEach(t => t.stop()); }
                    } catch (e) {}
                },
                exitFullscreen() {
                    if (document.fullscreenElement) {
                        document.exitFullscreen?.().catch(() => {});
                    }
                },
                openMaterial(material) {
                    this.materialListOpen = false;
                    this.viewingMaterial = material;
                    this.materialViewerOpen = true;
                },
                closeMaterialViewer() {
                    this.materialViewerOpen = false;
                    this.viewingMaterial = null;
                },
                acknowledgeWarning() {
                    this.violationWarningOpen = false;
                    if (this.violationWarningEventType === 'fullscreen_exit' && ! document.fullscreenElement) {
                        document.documentElement.requestFullscreen?.().catch(() => {});
                    }
                    this.violationWarningEventType = null;
                },
                async finishSubmit() {
                    if (this.submitting) { return; }
                    this.submitting = true;
                    clearInterval(this.timer);
                    this.eventAbortController?.abort();
                    await $wire.beginSubmission();
                    this.exitFullscreen();
                    await this.stopRecording();
                    window.location.reload();
                },
            }"
            x-init="
                tick(); timer = setInterval(() => tick(), 1000);
                if (! document.fullscreenElement) {
                    document.documentElement.requestFullscreen?.().catch(() => { needsFullscreenResume = true; });
                }
                $nextTick(() => startRecording());
                eventAbortController = new AbortController();
                const listenerOpts = { signal: eventAbortController.signal };
                document.addEventListener('visibilitychange', () => { if (document.hidden) { handleViolation('tab_switch', 'medium'); } }, listenerOpts);
                window.addEventListener('blur', () => handleViolation('window_blur', 'low'), listenerOpts);
                document.addEventListener('copy', () => handleViolation('copy_paste', 'medium'), listenerOpts);
                document.addEventListener('paste', () => handleViolation('copy_paste', 'medium'), listenerOpts);
                document.addEventListener('contextmenu', (e) => { e.preventDefault(); handleViolation('right_click', 'low'); }, listenerOpts);
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'F12' || ((e.ctrlKey || e.metaKey) && e.shiftKey && ['I','J','C'].includes(e.key))) {
                        handleViolation('devtools_opened', 'high');
                    }
                    if ((e.ctrlKey || e.metaKey) && ['t', 'n'].includes(e.key.toLowerCase())) {
                        handleViolation('navigation_attempt', 'medium', { reason: 'new_tab_shortcut' });
                    }
                }, listenerOpts);
                document.addEventListener('click', (e) => {
                    const anchor = e.target.closest && e.target.closest('a[href]');
                    if (! anchor) { return; }
                    if (anchor.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) {
                        e.preventDefault();
                        handleViolation('navigation_attempt', 'medium', { reason: 'new_tab_link' });
                    }
                }, { ...listenerOpts, capture: true });
                document.addEventListener('auxclick', (e) => {
                    if (e.button !== 1) { return; }
                    const anchor = e.target.closest && e.target.closest('a[href]');
                    if (! anchor) { return; }
                    e.preventDefault();
                    handleViolation('navigation_attempt', 'medium', { reason: 'middle_click_new_tab' });
                }, { ...listenerOpts, capture: true });
                document.addEventListener('fullscreenchange', () => {
                    if (! document.fullscreenElement) {
                        handleViolation('fullscreen_exit', 'medium');
                        resumeFullscreen();
                    } else {
                        needsFullscreenResume = false;
                    }
                }, listenerOpts);
            "
            x-on:destroy="clearInterval(timer); eventAbortController?.abort(); readingDetector?.stop(); stopRecording()"
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

                    @if (app()->isLocal())
                        <button
                            type="button"
                            @click="deadline = new Date(Date.now() + 2000).toISOString()"
                            class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition"
                        >
                            Dev: 2s
                        </button>

                        <button
                            type="button"
                            @click="violationsDisabled = ! violationsDisabled"
                            :class="violationsDisabled ? 'border-error text-error' : 'border-outline text-on-surface'"
                            class="px-space-md py-space-xs border rounded-lg font-label-sm text-label-sm hover:bg-surface-container transition"
                        >
                            <span x-text="violationsDisabled ? 'Dev: Violations Disabled' : 'Dev: Disable Violations'"></span>
                        </button>
                    @endif

                    <button
                        type="button"
                        @click="confirmOpen = true"
                        wire:loading.attr="disabled"
                        wire:target="beginSubmission"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                    >
                        <span wire:loading wire:target="beginSubmission" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
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

                    <div x-show="allowedTypes === 'open_book' && examMaterials.length" x-cloak>
                        <button
                            type="button"
                            @click="materialListOpen = true"
                            class="w-full flex items-center gap-space-sm px-space-md py-space-sm bg-surface-container rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container/70 transition"
                        >
                            <span class="material-symbols-outlined text-[18px]">folder_open</span>
                            Course Materials
                            <span class="ml-auto text-body-xs text-secondary" x-text="examMaterials.length"></span>
                        </button>
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
                                    <span x-show="noFaceSuspected" x-cloak class="absolute inset-x-0 bottom-0 px-space-sm py-1 bg-error/90 text-white text-body-xs text-center">
                                        Face not detected
                                    </span>
                                    <span x-show="facingDownSuspected && !noFaceSuspected" x-cloak class="absolute inset-x-0 bottom-0 px-space-sm py-1 bg-error/90 text-white text-body-xs text-center">
                                        Head down detected
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
                                wire:target="beginSubmission"
                                @click="confirmOpen = false; finishSubmit()"
                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                            >
                                <span wire:loading wire:target="beginSubmission" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-teleport="body">
                <div
                    x-show="violationWarningOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 px-gutter"
                >
                    <div class="bg-surface border border-error/40 rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                        <div class="flex items-center gap-space-md">
                            <span class="material-symbols-outlined text-error text-[28px]" data-weight="fill">warning</span>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">Warning</h2>
                        </div>
                        <p class="font-body-md text-body-md text-secondary" x-text="violationWarningMessage"></p>
                        <div class="flex items-center justify-end">
                            <button
                                type="button"
                                @click="acknowledgeWarning()"
                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                            >
                                I Understand
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-teleport="body">
                <div
                    x-show="materialListOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[124] flex items-center justify-center bg-black/50 px-gutter"
                    @click.self="materialListOpen = false"
                >
                    <div class="bg-surface border border-outline-variant rounded-lg w-full h-full max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between gap-space-md px-space-lg py-space-md border-b border-outline-variant flex-shrink-0">
                            <h2 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-space-sm">
                                <span class="material-symbols-outlined text-[20px]">folder_open</span>
                                Course Materials
                            </h2>
                            <button type="button" @click="materialListOpen = false" class="text-secondary hover:text-on-surface transition">
                                <span class="material-symbols-outlined text-[22px]">close</span>
                            </button>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-space-md px-space-lg py-space-md border-b border-outline-variant flex-shrink-0">
                            <div class="flex-1 h-9 flex items-center gap-space-sm px-space-md border border-outline rounded-lg focus-within:ring-2 focus-within:ring-primary/50">
                                <span class="material-symbols-outlined text-[18px] leading-none text-secondary">search</span>
                                <input
                                    type="text"
                                    x-model="materialSearch"
                                    placeholder="Search materials..."
                                    class="w-full border-0 bg-transparent font-body-sm text-body-sm focus:outline-none focus:ring-0"
                                />
                            </div>

                            <select
                                x-model="materialSessionFilter"
                                class="h-9 px-space-md border border-outline rounded-lg font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50"
                            >
                                <option value="">All Sessions</option>
                                <template x-for="session in examSessions" :key="session.id">
                                    <option :value="session.id" x-text="session.title"></option>
                                </template>
                            </select>
                        </div>

                        <div class="overflow-y-auto p-space-lg flex-1">
                            <div class="grid grid-cols-3 sm:grid-cols-6 md:grid-cols-8 gap-space-lg">
                                <template x-for="material in filteredMaterials" :key="material.id">
                                    <button
                                        type="button"
                                        @click="openMaterial(material)"
                                        class="flex flex-col items-center gap-space-sm p-space-md rounded-lg hover:bg-surface-container transition text-center"
                                    >
                                        <span class="text-4xl" x-text="material.icon"></span>
                                        <span class="w-full truncate font-body-xs text-body-xs text-on-surface" x-text="material.title"></span>
                                    </button>
                                </template>
                            </div>

                            <p x-show="! filteredMaterials.length" x-cloak class="text-center text-body-sm text-secondary py-space-xl">
                                No materials found.
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-teleport="body">
                <div
                    x-show="materialViewerOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[125] flex items-center justify-center bg-black/70 px-gutter"
                >
                    <div class="bg-surface rounded-lg p-space-lg max-w-4xl w-full max-h-[90vh] flex flex-col gap-space-md">
                        <div class="flex items-center justify-between gap-space-md flex-shrink-0">
                            <h2 class="font-headline-sm text-headline-sm text-on-surface truncate" x-text="viewingMaterial?.title"></h2>
                            <div class="flex items-center gap-space-sm flex-shrink-0">
                                <button
                                    type="button"
                                    @click="closeMaterialViewer()"
                                    class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition"
                                >
                                    Close
                                </button>
                            </div>
                        </div>

                        <div
                            class="bg-surface-container rounded-lg flex-1 overflow-auto"
                            :class="viewingMaterial?.type === 'Markdown' ? '' : 'aspect-video'"
                        >
                            <template x-if="viewingMaterial?.type === 'Video'">
                                <video :src="viewingMaterial.url" width="100%" height="100%" controls class="w-full h-full">
                                    Your browser does not support the video tag.
                                </video>
                            </template>

                            <template x-if="viewingMaterial?.type === 'PDF'">
                                <embed :src="viewingMaterial.url" type="application/pdf" width="100%" height="100%" class="rounded" />
                            </template>

                            <template x-if="viewingMaterial?.type === 'Audio'">
                                <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                                    <span class="text-5xl">🎵</span>
                                    <p class="text-body-md text-on-surface" x-text="viewingMaterial.title"></p>
                                    <audio :src="viewingMaterial.url" controls class="w-full">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                            </template>

                            <template x-if="viewingMaterial?.type === 'Image'">
                                <div class="w-full h-full flex items-center justify-center overflow-auto">
                                    <img :src="viewingMaterial.url" :alt="viewingMaterial.title" class="max-w-full max-h-full" />
                                </div>
                            </template>

                            <template x-if="viewingMaterial?.type === 'Interactive'">
                                <iframe
                                    :src="viewingMaterial.url"
                                    class="w-full h-full rounded border-0"
                                    sandbox="allow-scripts allow-same-origin allow-forms"
                                    :title="viewingMaterial.title"
                                ></iframe>
                            </template>

                            <template x-if="viewingMaterial?.type === 'Presentation'">
                                <iframe
                                    :src="'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(viewingMaterial.url)"
                                    width="100%"
                                    height="100%"
                                    frameborder="0"
                                    class="rounded"
                                ></iframe>
                            </template>

                            <template x-if="viewingMaterial?.type === 'Document' && viewingMaterial.extension === 'pdf'">
                                <embed :src="viewingMaterial.url" type="application/pdf" width="100%" height="100%" class="rounded" />
                            </template>

                            <template x-if="viewingMaterial?.type === 'Document' && ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'].includes(viewingMaterial.extension)">
                                <iframe
                                    :src="'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(viewingMaterial.url)"
                                    width="100%"
                                    height="100%"
                                    frameborder="0"
                                    class="rounded"
                                ></iframe>
                            </template>

                            <template x-if="viewingMaterial?.type === 'Document' && ['txt', 'csv', 'md'].includes(viewingMaterial.extension)">
                                <div
                                    x-data="{ text: null, error: null }"
                                    x-init="
                                        fetch(viewingMaterial.url)
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

                            <template x-if="viewingMaterial?.type === 'Document' && ! ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'csv', 'md'].includes(viewingMaterial.extension)">
                                <div class="w-full h-full flex flex-col items-center justify-center gap-space-md p-space-lg">
                                    <span class="text-5xl">📝</span>
                                    <p class="text-body-md text-on-surface" x-text="viewingMaterial.title"></p>
                                    <a
                                        :href="viewingMaterial.url"
                                        download
                                        class="px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition"
                                    >
                                        Download Document
                                    </a>
                                </div>
                            </template>

                            <template x-if="viewingMaterial?.type === 'Markdown'">
                                <div
                                    x-data="{ html: null, error: null }"
                                    x-init="
                                        fetch(viewingMaterial.url)
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
                    x-show="needsFullscreenResume && ! submitting"
                    x-cloak
                    class="fixed inset-0 z-[140] flex flex-col items-center justify-center gap-space-lg bg-surface px-gutter"
                >
                    <span class="material-symbols-outlined text-error text-[48px]">fullscreen</span>
                    <p class="font-label-md text-label-md text-on-surface text-center max-w-sm">Full-screen mode is required to continue this exam.</p>
                    <button
                        type="button"
                        @click="resumeFullscreen()"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        Return to Full-Screen
                    </button>
                </div>
            </template>

            <template x-teleport="body">
                <div
                    x-show="submitting"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="fixed inset-0 z-[120] flex flex-col items-center justify-center gap-space-lg bg-surface"
                >
                    <template x-if="disqualifying">
                        <div class="flex flex-col items-center gap-space-lg">
                            <span class="material-symbols-outlined text-error text-[48px]">block</span>
                            <p class="font-label-md text-label-md text-error">You have been disqualified from this exam. Submitting your exam, please wait…</p>
                        </div>
                    </template>
                    <template x-if="!disqualifying">
                        <div class="flex flex-col items-center gap-space-lg">
                            <span class="material-symbols-outlined animate-spin text-primary text-[48px]">progress_activity</span>
                            <p class="font-label-md text-label-md text-on-surface">Submitting your exam, please wait…</p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    @elseif ($submitting)
        <div
            x-data="{ eventSource: null }"
            x-init="
                eventSource = new EventSource(@js(route('assessments.final-exam.proctor.submission-status-stream', $assessment)));
                eventSource.onmessage = (e) => {
                    const data = JSON.parse(e.data);
                    if (data.status !== 'submitting') {
                        eventSource.close();
                        window.location.reload();
                    }
                };
            "
            x-on:destroy="eventSource?.close()"
            class="fixed inset-0 z-[100] bg-surface flex flex-col items-center justify-center gap-space-lg px-gutter"
        >
            <span class="material-symbols-outlined animate-spin text-primary text-[48px]">progress_activity</span>
            <p class="font-label-md text-label-md text-on-surface text-center">Submitting your exam, please wait…</p>
        </div>
    @elseif ($justSubmitted)
        <div class="fixed inset-0 z-[100] bg-surface flex flex-col items-center justify-center gap-space-lg px-gutter">
            <span class="material-symbols-outlined text-success text-[64px]" data-weight="fill">check_circle</span>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Exam Submitted</h2>
            <p class="text-body-md text-on-surface-variant text-center max-w-md">Your exam has been submitted successfully.</p>
            <a
                href="{{ route('assessments.final-exam.show', $assessment) }}"
                wire:navigate
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
            >
                Back to Exam Overview
            </a>
        </div>
    @elseif ($disqualified)
        <div class="fixed inset-0 z-[100] bg-surface flex flex-col items-center justify-center gap-space-lg px-gutter">
            <span class="material-symbols-outlined text-error text-[64px]" data-weight="fill">block</span>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Disqualified</h2>
            <a
                href="{{ route('assessments.final-exam.show', $assessment) }}"
                wire:navigate
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
            >
                Back to Exam Overview
            </a>
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
                        this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                        this.$nextTick(() => { if (this.$refs.cameraPreview) { this.$refs.cameraPreview.srcObject = this.cameraStream; } });
                    } catch (e) {
                        this.cameraError = e.name === 'NotAllowedError'
                            ? 'Camera and microphone access was denied. Please allow permissions and try again.'
                            : (e.name === 'NotFoundError' ? 'No camera or microphone was found on this device.' : `Camera access was denied or unavailable (${e.name || 'unknown error'}).`);
                    }
                },
                async checkScreen() {
                    this.screenError = null;
                    try {
                        const stream = await navigator.mediaDevices.getDisplayMedia({ video: { displaySurface: 'monitor' }, audio: true });
                        const track = stream.getVideoTracks()[0];
                        if (track.getSettings().displaySurface !== 'monitor') {
                            stream.getTracks().forEach(t => t.stop());
                            this.screenError = 'You must share your entire screen, not a window or tab. Click Share Screen and choose &quot;Entire Screen&quot;.';
                            return;
                        }
                        if (stream.getAudioTracks().length === 0) {
                            stream.getTracks().forEach(t => t.stop());
                            this.screenError = 'You must also share audio. Click Share Screen and enable the &quot;Share audio&quot; (or &quot;Share tab audio&quot;) option.';
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
                    document.documentElement.requestFullscreen?.().catch(() => {});
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

@push('scripts')
    @include('partials.markdown-renderer-script')
@endpush
