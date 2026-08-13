@section('title', 'Pre-flight Checks — '.$assessment->title)

<div
    class="min-h-screen bg-surface-container/30 flex items-center justify-center px-gutter py-space-xl"
    x-data="{
        speedTesting: false,
        speedResult: null,
        speedPhase: null,
        speedMbps: 0,
        displayMbps: 0,
        downloadMbps: 0,
        uploadMbps: 0,
        gaugeDeg: -90,
        gaugeRaf: null,
        cameraStream: null,
        cameraError: null,
        screenStream: null,
        screenError: null,
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
                const url = '{{ route('proctor.speed-test-download') }}?cb=' + Date.now() + Math.random();
                const res = await fetch(url, { cache: 'no-store' });
                const reader = res.body.getReader();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) { break; }
                    receivedBytes += value.length;
                    const elapsedSec = (performance.now() - started) / 1000;
                    if (elapsedSec > 0.15) {
                        const instant = (receivedBytes * 8 / 1_000_000) / elapsedSec;
                        ema = ema === null ? instant : (ema * 0.7 + instant * 0.3);
                        this.animateGaugeTo(ema);
                    }
                    if ((performance.now() - started) >= this.phaseDurationMs) {
                        reader.cancel();
                        break;
                    }
                }
            }

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
                await fetch('{{ route('proctor.speed-test-upload') }}', {
                    method: 'POST',
                    cache: 'no-store',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/octet-stream' },
                    body: chunk,
                });
                sentBytes += chunk.size;
                const chunkElapsedSec = (performance.now() - chunkStarted) / 1000;
                const instant = (chunk.size * 8 / 1_000_000) / chunkElapsedSec;
                ema = ema === null ? instant : (ema * 0.6 + instant * 0.4);
                this.animateGaugeTo(ema);
            }

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
                this.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                $wire.markCheckPassed('screen');
                this.screenStream.getVideoTracks()[0].addEventListener('ended', () => {
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
>
    <div class="bg-surface border border-outline-variant rounded-lg p-space-xl max-w-xl w-full space-y-space-lg">
        <!-- Step indicator -->
        <div class="flex items-center justify-center gap-space-sm">
            @foreach (['speed' => '1', 'camera' => '2', 'screen' => '3'] as $stepKey => $stepNumber)
                <div class="flex items-center gap-space-sm">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-label-sm text-label-sm
                        {{ $step === $stepKey ? 'bg-primary text-on-primary' : ($checksPassed[$stepKey] ? 'bg-success/10 text-success' : 'bg-surface-container text-on-surface-variant') }}">
                        @if ($checksPassed[$stepKey] && $step !== $stepKey)
                            <span class="material-symbols-outlined text-[18px]" data-weight="fill">check</span>
                        @else
                            {{ $stepNumber }}
                        @endif
                    </div>
                    @if ($stepNumber !== '3')
                        <div class="w-8 h-0.5 bg-outline-variant"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="text-center">
            <h1 class="font-headline-md text-headline-md text-on-surface">Pre-flight Checks</h1>
            <p class="text-body-sm text-on-surface-variant mt-space-xs">
                This exam is proctored. Complete each check to start "{{ $assessment->title }}".
            </p>
        </div>

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
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
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
                        wire:click="goToStep('screen')"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        Continue
                    </button>
                </div>
            </div>
        @endif

        <!-- Step 3: Screen capture -->
        @if ($step === 'screen')
            <div class="space-y-space-lg">
                <h3 class="font-label-lg text-label-lg text-on-surface text-center">3. Screen Capture</h3>
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
                    <button type="button" wire:click="goToStep('camera')" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                        Back
                    </button>
                    @if ($allChecksPassed)
                        <a
                            href="{{ route('assessments.final-exam.proctor.show', $assessment) }}"
                            wire:navigate
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
