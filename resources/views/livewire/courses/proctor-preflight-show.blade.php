@section('title', $assessment->title)

<div
    class="min-h-screen bg-surface-container/30"
    x-data="{
        speedTesting: false,
        speedResult: null,
        speedPhase: null,
        speedMbps: 0,
        displayMbps: 0,
        downloadMbps: 0,
        uploadMbps: 0,
        speedProgress: 0,
        gaugeDeg: -90,
        gaugeRaf: null,
        cameraStream: null,
        cameraError: null,
        screenStream: null,
        screenError: null,
        micStream: null,
        micError: null,
        micLevel: 0,
        micAudioCtx: null,
        micRaf: null,
        minMbps: 3,
        maxGaugeMbps: 100,
        phaseDurationMs: 5000,
        animateGaugeTo(mbps) {
            this.speedMbps = mbps;
            if (this.gaugeRaf) { return; }
            const step = () => {
                const diff = this.speedMbps - this.displayMbps;
                if (Math.abs(diff) < 0.05) {
                    this.displayMbps = this.speedMbps;
                    this.gaugeRaf = null;
                } else {
                    this.displayMbps += diff * 0.12;
                    this.gaugeRaf = requestAnimationFrame(step);
                }
                const clamped = Math.min(this.displayMbps, this.maxGaugeMbps);
                this.gaugeDeg = -90 + (clamped / this.maxGaugeMbps) * 180;
            };
            this.gaugeRaf = requestAnimationFrame(step);
        },
        async runSpeedCheck() {
            this.speedTesting = true;
            this.speedResult = null;
            this.speedMbps = 0;
            this.displayMbps = 0;
            this.downloadMbps = 0;
            this.uploadMbps = 0;
            this.speedProgress = 0;
            this.animateGaugeTo(0);
            try {
                this.speedPhase = 'download';
                this.downloadMbps = await this.measureDownload();

                this.speedPhase = null;
                this.animateGaugeTo(0);
                await new Promise(resolve => setTimeout(resolve, 1000));

                this.speedPhase = 'upload';
                this.uploadMbps = await this.measureUpload();

                this.speedPhase = null;
                this.speedProgress = 100;
                this.animateGaugeTo(0);

                const passed = this.downloadMbps >= this.minMbps && this.uploadMbps >= this.minMbps;
                this.speedResult = passed ? 'pass' : 'fail';
                if (passed) { $wire.markCheckPassed('speed'); } else { $wire.markCheckFailed('speed'); }
            } catch (e) {
                this.speedResult = 'fail';
                $wire.markCheckFailed('speed');
            } finally {
                this.speedTesting = false;
                this.speedPhase = null;
            }
        },
        async measureDownload() {
            const started = performance.now();
            let receivedBytes = 0;
            let ema = null;

            while ((performance.now() - started) < this.phaseDurationMs) {
                const url = '{{ route('proctor.speed-test-download', [], false) }}?cb=' + Date.now() + Math.random();
                const res = await fetch(url, { cache: 'no-store' });
                const reader = res.body.getReader();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) { break; }
                    receivedBytes += value.length;
                    const elapsedMs = performance.now() - started;
                    const elapsedSec = elapsedMs / 1000;
                    this.speedProgress = Math.min(elapsedMs / this.phaseDurationMs, 1) * 50;
                    if (elapsedSec > 0.15) {
                        const instant = (receivedBytes * 8 / 1_000_000) / elapsedSec;
                        ema = ema === null ? instant : (ema * 0.7 + instant * 0.3);
                        this.animateGaugeTo(ema);
                    }
                    if (elapsedMs >= this.phaseDurationMs) {
                        reader.cancel();
                        break;
                    }
                }
            }

            this.speedProgress = 50;
            const totalElapsedSec = (performance.now() - started) / 1000;
            const final = (receivedBytes * 8 / 1_000_000) / totalElapsedSec;
            this.animateGaugeTo(final);

            return final;
        },
        async measureUpload() {
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
            const chunk = new Blob([new Uint8Array(2 * 1024 * 1024).fill(1)]);
            const started = performance.now();
            let sentBytes = 0;
            let ema = null;

            while ((performance.now() - started) < this.phaseDurationMs) {
                const chunkStarted = performance.now();
                await fetch('{{ route('proctor.speed-test-upload', [], false) }}', {
                    method: 'POST',
                    cache: 'no-store',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/octet-stream' },
                    body: chunk,
                });
                sentBytes += chunk.size;
                const chunkElapsedMs = performance.now() - started;
                const chunkElapsedSec = (performance.now() - chunkStarted) / 1000;
                this.speedProgress = 50 + Math.min(chunkElapsedMs / this.phaseDurationMs, 1) * 50;
                const instant = (chunk.size * 8 / 1_000_000) / chunkElapsedSec;
                ema = ema === null ? instant : (ema * 0.6 + instant * 0.4);
                this.animateGaugeTo(ema);
            }

            this.speedProgress = 100;
            const totalElapsedSec = (performance.now() - started) / 1000;
            const final = (sentBytes * 8 / 1_000_000) / totalElapsedSec;
            this.animateGaugeTo(final);

            return final;
        },
        async runCameraCheck() {
            this.cameraError = null;
            if (! window.isSecureContext) {
                this.cameraError = 'Camera access requires a secure (HTTPS) connection.';
                $wire.markCheckFailed('camera');
                return;
            }
            if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) {
                this.cameraError = 'Your browser does not support camera access.';
                $wire.markCheckFailed('camera');
                return;
            }
            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                this.$nextTick(() => { if (this.$refs.cameraPreview) { this.$refs.cameraPreview.srcObject = this.cameraStream; } });
                $wire.markCheckPassed('camera');
            } catch (e) {
                this.cameraError = e.name === 'NotAllowedError'
                    ? 'Camera access was denied. Please allow camera permission in your browser settings and try again.'
                    : (e.name === 'NotFoundError' ? 'No camera was found on this device.' : `Camera access was denied or unavailable (${e.name || 'unknown error'}).`);
                $wire.markCheckFailed('camera');
            }
        },
        async runMicCheck() {
            this.micError = null;
            if (! window.isSecureContext) {
                this.micError = 'Microphone access requires a secure (HTTPS) connection.';
                $wire.markCheckFailed('mic');
                return;
            }
            if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) {
                this.micError = 'Your browser does not support microphone access.';
                $wire.markCheckFailed('mic');
                return;
            }
            try {
                this.micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                $wire.markCheckPassed('mic');

                this.micAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const source = this.micAudioCtx.createMediaStreamSource(this.micStream);
                const analyser = this.micAudioCtx.createAnalyser();
                analyser.fftSize = 512;
                source.connect(analyser);
                const data = new Uint8Array(analyser.frequencyBinCount);

                const tick = () => {
                    if (! this.micStream) { return; }
                    analyser.getByteTimeDomainData(data);
                    let sumSquares = 0;
                    for (let i = 0; i < data.length; i++) {
                        const v = (data[i] - 128) / 128;
                        sumSquares += v * v;
                    }
                    this.micLevel = Math.min(Math.sqrt(sumSquares / data.length) * 4, 1);
                    this.micRaf = requestAnimationFrame(tick);
                };
                tick();
            } catch (e) {
                this.micError = e.name === 'NotAllowedError'
                    ? 'Microphone access was denied. Please allow microphone permission in your browser settings and try again.'
                    : (e.name === 'NotFoundError' ? 'No microphone was found on this device.' : `Microphone access was denied or unavailable (${e.name || 'unknown error'}).`);
                $wire.markCheckFailed('mic');
            }
        },
        stopMicCheck() {
            if (this.micRaf) { cancelAnimationFrame(this.micRaf); this.micRaf = null; }
            if (this.micStream) { this.micStream.getTracks().forEach(t => t.stop()); this.micStream = null; }
            if (this.micAudioCtx) { this.micAudioCtx.close(); this.micAudioCtx = null; }
            this.micLevel = 0;
        },
        stopScreenCheck() {
            if (this.screenStream) { this.screenStream.getTracks().forEach(t => t.stop()); this.screenStream = null; }
        },
        stopAllChecks() {
            this.stopMicCheck();
            this.stopScreenCheck();
            if (this.cameraStream) { this.cameraStream.getTracks().forEach(t => t.stop()); this.cameraStream = null; }
        },
        async runScreenCheck() {
            this.screenError = null;
            if (! window.isSecureContext) {
                this.screenError = 'Screen sharing requires a secure (HTTPS) connection.';
                $wire.markCheckFailed('screen');
                return;
            }
            if (! navigator.mediaDevices || ! navigator.mediaDevices.getDisplayMedia) {
                this.screenError = 'Your browser does not support screen sharing.';
                $wire.markCheckFailed('screen');
                return;
            }
            try {
                this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: { displaySurface: 'monitor' } });
                const track = this.screenStream.getVideoTracks()[0];
                if (track.getSettings().displaySurface !== 'monitor') {
                    track.stop();
                    this.screenStream = null;
                    this.screenError = 'You must share your entire screen, not a window or tab. Please try again and choose &quot;Entire Screen&quot;.';
                    $wire.markCheckFailed('screen');
                    return;
                }
                $wire.markCheckPassed('screen');
                track.addEventListener('ended', () => {
                    this.screenStream = null;
                    $wire.markCheckFailed('screen');
                });
            } catch (e) {
                this.screenError = e.name === 'NotAllowedError'
                    ? 'Screen sharing was denied. Please allow screen sharing when prompted and try again.'
                    : `Screen sharing was denied or unavailable (${e.name || 'unknown error'}).`;
                $wire.markCheckFailed('screen');
            }
        },
    }"
    x-init="$el.closest('main')?.scrollTo(0, 0)"
    x-on:destroy="stopAllChecks()"
>
    <div class="fixed top-0 inset-x-0 flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant bg-surface z-10">
        <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $assessment->title }}</h2>
        <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">Proctored</span>
    </div>

    <div class="min-h-screen flex items-center justify-center px-gutter py-space-xl pt-24">
    <div class="bg-surface border border-outline-variant rounded-lg p-space-xl max-w-xl w-full space-y-space-lg">
        @if ($step !== 'instructions')
            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-space-sm">
                @foreach (['speed' => '1', 'camera' => '2', 'mic' => '3', 'screen' => '4'] as $stepKey => $stepNumber)
                    <div class="flex items-center gap-space-sm">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-label-sm text-label-sm
                            {{ $step === $stepKey ? 'bg-primary text-on-primary' : ($checksPassed[$stepKey] ? 'bg-success/10 text-success' : 'bg-surface-container text-on-surface-variant') }}">
                            @if ($checksPassed[$stepKey] && $step !== $stepKey)
                                <span class="material-symbols-outlined text-[18px]" data-weight="fill">check</span>
                            @else
                                {{ $stepNumber }}
                            @endif
                        </div>
                        @if ($stepNumber !== '4')
                            <div class="w-8 h-0.5 bg-outline-variant"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="text-center">
            <p class="text-body-sm text-on-surface-variant mt-space-xs">
                This exam is proctored. Complete each check below to begin.
            </p>
        </div>

        <!-- Step 0: Instructions -->
        @if ($step === 'instructions')
            <div class="space-y-space-lg">
                @if ($finalExam->instructions)
                    <div class="bg-surface-container/50 border border-outline-variant rounded-lg p-space-lg">
                        <p class="font-label-md text-label-md text-on-surface mb-space-sm">Instructions</p>
                        <div class="rte-content prose prose-sm max-w-none text-on-surface-variant">{!! $finalExam->instructions !!}</div>
                    </div>
                @endif

                <div class="flex justify-end pt-space-md border-t border-outline-variant">
                    <button
                        type="button"
                        wire:click="goToStep('speed')"
                        wire:loading.attr="disabled"
                        wire:target="goToStep('speed')"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                    >
                        <span wire:loading wire:target="goToStep('speed')" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        Continue
                    </button>
                </div>
            </div>
        @endif

        <!-- Step 1: Internet speed -->
        @if ($step === 'speed')
            <div class="space-y-space-lg">
                <h3 class="font-label-lg text-label-lg text-on-surface text-center">1. Internet Speed</h3>
                <p class="text-body-sm text-on-surface-variant text-center">A minimum of 3 Mbps for both download and upload is required.</p>

                <!-- Speedometer gauge -->
                <div class="mx-auto w-56 space-y-space-sm">
                    <svg viewBox="0 0 200 115" class="w-full h-auto">
                        <path d="M 10 100 A 90 90 0 0 1 190 100" fill="none" stroke="currentColor" class="text-surface-container" stroke-width="14" stroke-linecap="round" />
                        <path d="M 10 100 A 90 90 0 0 1 190 100" fill="none" stroke="currentColor" class="text-primary" stroke-width="14" stroke-linecap="round" style="transition: stroke-dashoffset 0.15s linear;"
                            :stroke-dasharray="283" :stroke-dashoffset="283 - (283 * Math.min(displayMbps, maxGaugeMbps) / maxGaugeMbps)" />
                        <line x1="100" y1="100" x2="100" y2="30" stroke="currentColor" class="text-on-surface" stroke-width="3" stroke-linecap="round"
                            :style="'transform: rotate(' + gaugeDeg + 'deg); transform-origin: 100px 100px;'" />
                        <circle cx="100" cy="100" r="6" fill="currentColor" class="text-on-surface" />
                    </svg>
                    <div class="text-center">
                        <span class="font-headline-sm text-headline-sm text-on-surface" x-text="displayMbps.toFixed(1)"></span>
                        <span class="text-body-xs text-on-surface-variant">Mbps</span>
                        <p class="text-body-xs text-on-surface-variant mt-space-xs" x-show="speedPhase" x-cloak>
                            Testing <span x-text="speedPhase"></span>…
                        </p>
                        <p class="text-body-xs text-on-surface-variant mt-space-xs" x-show="speedTesting && !speedPhase" x-cloak>
                            Preparing upload test…
                        </p>
                    </div>
                </div>

                <div x-show="speedTesting" x-cloak class="mx-auto w-56 space-y-space-xs">
                    <div class="h-2 w-full bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="transition: width 0.1s linear;" :style="'width: ' + speedProgress + '%'"></div>
                    </div>
                    <p class="text-body-xs text-on-surface-variant text-center" x-text="Math.round(speedProgress) + '%'"></p>
                </div>

                <div x-show="!speedTesting && speedResult" x-cloak class="flex items-center justify-center gap-space-lg text-body-sm text-on-surface-variant">
                    <span>Download: <span class="font-medium text-on-surface" x-text="downloadMbps.toFixed(1) + ' Mbps'"></span></span>
                    <span>Upload: <span class="font-medium text-on-surface" x-text="uploadMbps.toFixed(1) + ' Mbps'"></span></span>
                </div>

                <div class="text-center">
                    <button
                        type="button"
                        @click="runSpeedCheck()"
                        :disabled="speedTesting"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                    >
                        <span x-show="!speedTesting">Run Speed Test</span>
                        <span x-show="speedTesting" x-cloak>Testing…</span>
                    </button>
                    <p x-show="speedResult === 'fail'" x-cloak class="text-body-sm text-error mt-space-sm">
                        Connection too slow (minimum 3 Mbps required for both download and upload). Please try again on a more stable network.
                    </p>
                    <p x-show="speedResult === 'pass'" x-cloak class="text-body-sm text-success mt-space-sm">Speed check passed.</p>
                </div>

                <div class="flex justify-end pt-space-md border-t border-outline-variant">
                    <button
                        type="button"
                        x-show="speedResult === 'pass'"
                        x-cloak
                        wire:click="goToStep('camera')"
                        wire:loading.attr="disabled"
                        wire:target="goToStep('camera')"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                    >
                        <span wire:loading wire:target="goToStep('camera')" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        Continue
                    </button>
                </div>
            </div>
        @endif

        <!-- Step 2: Camera -->
        @if ($step === 'camera')
            <div class="space-y-space-lg" x-init="if (! cameraStream) { runCameraCheck(); }">
                <h3 class="font-label-lg text-label-lg text-on-surface text-center">2. Camera</h3>
                <p class="text-body-sm text-on-surface-variant text-center">We need to see your face is visible for the duration of the exam.</p>

                <video x-ref="cameraPreview" x-show="cameraStream" x-cloak autoplay muted playsinline class="w-full max-w-sm mx-auto rounded-lg bg-black aspect-video"></video>

                <div class="text-center">
                    <button
                        type="button"
                        @click="runCameraCheck()"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        <span x-show="!cameraStream">Enable Camera</span>
                        <span x-show="cameraStream" x-cloak>Retry</span>
                    </button>
                    <p x-show="cameraError" x-cloak class="text-body-sm text-error mt-space-sm" x-text="cameraError"></p>
                </div>

                <div class="flex items-center justify-between pt-space-md border-t border-outline-variant">
                    <button type="button" wire:click="goToStep('speed')" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                        Back
                    </button>
                    <button
                        type="button"
                        x-show="cameraStream"
                        x-cloak
                        wire:click="goToStep('mic')"
                        wire:loading.attr="disabled"
                        wire:target="goToStep('mic')"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                    >
                        <span wire:loading wire:target="goToStep('mic')" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        Continue
                    </button>
                </div>
            </div>
        @endif

        <!-- Step 3: Microphone -->
        @if ($step === 'mic')
            <div class="space-y-space-lg" x-init="if (! micStream) { runMicCheck(); }" x-on:destroy="stopMicCheck()">
                <h3 class="font-label-lg text-label-lg text-on-surface text-center">3. Microphone</h3>
                <p class="text-body-sm text-on-surface-variant text-center">We need to hear audio in your room during the exam.</p>

                <div class="max-w-xs mx-auto">
                    <div class="h-3 w-full bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="transition: width 0.05s linear;" :style="'width: ' + Math.round(micLevel * 100) + '%'"></div>
                    </div>
                    <p class="text-body-xs text-on-surface-variant text-center mt-space-xs" x-show="micStream" x-cloak>Say something to test your microphone.</p>
                </div>

                <div class="text-center">
                    <button
                        type="button"
                        @click="runMicCheck()"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        <span x-show="!micStream">Enable Microphone</span>
                        <span x-show="micStream" x-cloak>Retry</span>
                    </button>
                    <p x-show="micError" x-cloak class="text-body-sm text-error mt-space-sm" x-text="micError"></p>
                </div>

                <div class="flex items-center justify-between pt-space-md border-t border-outline-variant">
                    <button type="button" wire:click="goToStep('camera')" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                        Back
                    </button>
                    <button
                        type="button"
                        x-show="micStream"
                        x-cloak
                        wire:click="goToStep('screen')"
                        wire:loading.attr="disabled"
                        wire:target="goToStep('screen')"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                    >
                        <span wire:loading wire:target="goToStep('screen')" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        Continue
                    </button>
                </div>
            </div>
        @endif

        <!-- Step 4: Screen capture -->
        @if ($step === 'screen')
            <div class="space-y-space-lg">
                <h3 class="font-label-lg text-label-lg text-on-surface text-center">4. Screen Capture</h3>
                <p class="text-body-sm text-on-surface-variant text-center">Your screen will be recorded during the exam for review.</p>

                <div class="text-center">
                    <button
                        type="button"
                        @click="runScreenCheck()"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        <span x-show="!screenStream">Share Screen</span>
                        <span x-show="screenStream" x-cloak>Retry</span>
                    </button>
                    <p x-show="screenStream" x-cloak class="text-body-sm text-success mt-space-sm">Screen sharing is active.</p>
                    <p x-show="screenError" x-cloak class="text-body-sm text-error mt-space-sm" x-text="screenError"></p>
                </div>

                <div class="flex items-center justify-between pt-space-md border-t border-outline-variant">
                    <button type="button" wire:click="goToStep('mic')" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                        Back
                    </button>
                    @if ($allChecksPassed)
                        <a
                            href="{{ route('assessments.final-exam.proctor.show', $assessment) }}"
                            wire:navigate
                            @click="stopAllChecks()"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Start Exam
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
    </div>
</div>
