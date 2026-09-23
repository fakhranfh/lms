@php
    $questions = [
        ['text' => 'Which of the following best describes a linear equation?', 'options' => ['A straight-line relationship between two variables', 'A curve with more than one turning point', 'An equation with no variables', 'A relationship that only applies to circles']],
        ['text' => 'Solve for x: 2x + 6 = 14.', 'options' => ['x = 4', 'x = 10', 'x = 2', 'x = 8']],
        ['text' => 'What is the slope of the line y = 3x - 5?', 'options' => ['3', '-5', '5', '-3']],
        ['text' => 'Which point lies on the line y = x + 1?', 'options' => ['(2, 3)', '(2, 1)', '(0, 0)', '(1, 0)']],
        ['text' => 'What does the y-intercept represent on a graph?', 'options' => ['Where the line crosses the y-axis', 'Where the line crosses the x-axis', 'The steepness of the line', 'The midpoint of the line']],
        ['text' => 'Which equation represents a vertical line?', 'options' => ['x = 4', 'y = 4', 'y = x', 'x + y = 4']],
        ['text' => 'If two lines are parallel, their slopes are:', 'options' => ['Equal', 'Negative reciprocals', 'Always zero', 'Always undefined']],
        ['text' => 'What is the standard form of a linear equation?', 'options' => ['Ax + By = C', 'y = mx + b', 'x = my + b', 'Ax^2 + Bx + C = 0']],
    ];
@endphp

<div class="reveal flex flex-col gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.1s">
    <div>
        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Exam proctoring</h3>
        <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Live sessions capture snapshots and integrity events during timed exams, so teacher can review anything flagged after the fact.</p>
    </div>

    <div class="grid gap-space-lg lg:grid-cols-2">
        <!-- Preview 1: Pre-flight check -->
        <div>
            <p class="font-label-sm text-label-sm text-[--lp-ink]">Pre-flight check</p>
            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-muted]">Before an exam can start, students pass a connection, camera, and screen-share check.</p>

            <div class="mt-space-sm overflow-hidden rounded-lg border border-[--lp-outline] bg-[--lp-surface]" x-data="{
                step: 'speed',
                steps: ['speed', 'camera', 'screen'],
                passed: { speed: false, camera: false, screen: false },
                testing: false,
                faceDirections: ['right', 'left', 'up', 'down'],
                faceDone: [],
                runSpeedTest() {
                    this.testing = true;
                    setTimeout(() => { this.testing = false; this.passed.speed = true; }, 1200);
                },
                markFace(direction) {
                    if (this.faceDone.includes(direction)) { return; }
                    this.faceDone.push(direction);
                    if (this.faceDone.length === this.faceDirections.length) { this.passed.camera = true; }
                },
                shareScreen() { this.passed.screen = true; },
                goTo(target) { this.step = target; },
            }">
                <div class="flex items-center justify-center gap-space-sm border-b border-[--lp-outline] px-space-md py-space-md">
                    <template x-for="(s, index) in steps" :key="s">
                        <div class="flex items-center gap-space-sm">
                            <button
                                type="button"
                                @click="goTo(s)"
                                class="flex h-8 w-8 items-center justify-center rounded-full font-label-sm text-label-sm transition-colors"
                                :class="step === s ? 'bg-[--lp-primary] text-[--lp-on-primary]' : (passed[s] ? 'bg-success/10 text-success' : 'bg-[--lp-bg] text-[--lp-muted]')"
                            >
                                <span x-show="passed[s] && step !== s" x-cloak aria-hidden="true">&check;</span>
                                <span x-show="! (passed[s] && step !== s)" x-text="index + 1"></span>
                            </button>
                            <div class="h-0.5 w-8 bg-[--lp-outline]" x-show="index < steps.length - 1"></div>
                        </div>
                    </template>
                </div>

                <div class="space-y-space-md p-space-lg text-center">
                    <div x-show="step === 'speed'" x-cloak class="space-y-space-md">
                        <p class="font-label-sm text-label-sm text-[--lp-ink]">1. Internet Speed</p>
                        <p class="font-body-sm text-body-sm text-[--lp-muted]">A minimum of 3 Mbps up and down is required.</p>
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-4 border-[--lp-primary]/20" :class="testing ? 'animate-pulse' : ''">
                            <span class="material-symbols-outlined text-[--lp-primary]">speed</span>
                        </div>
                        <button
                            type="button"
                            @click="runSpeedTest()"
                            :disabled="testing"
                            class="rounded-lg bg-[--lp-primary] px-space-lg py-space-sm font-label-sm text-label-sm text-[--lp-on-primary] disabled:opacity-50"
                        >
                            <span x-show="!testing">Run Speed Test</span>
                            <span x-show="testing" x-cloak>Testing…</span>
                        </button>
                        <p x-show="passed.speed" x-cloak class="font-label-sm text-label-sm text-success">Speed check passed.</p>
                        <div class="flex justify-end border-t border-[--lp-outline] pt-space-md">
                            <button type="button" x-show="passed.speed" x-cloak @click="goTo('camera')" class="rounded-lg bg-[--lp-primary] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]">Continue</button>
                        </div>
                    </div>

                    <div x-show="step === 'camera'" x-cloak class="space-y-space-md">
                        <p class="font-label-sm text-label-sm text-[--lp-ink]">2. Camera &amp; Face Verification</p>
                        <p class="font-body-sm text-body-sm text-[--lp-muted]">Turn to face each direction as prompted.</p>
                        <div class="grid grid-cols-4 gap-space-sm">
                            <template x-for="direction in faceDirections" :key="direction">
                                <button
                                    type="button"
                                    @click="markFace(direction)"
                                    class="flex flex-col items-center gap-space-xs rounded-lg border px-space-sm py-space-sm transition-colors"
                                    :class="faceDone.includes(direction) ? 'border-success bg-success/10 text-success' : 'border-[--lp-outline] text-[--lp-muted] hover:text-[--lp-ink]'"
                                >
                                    <span class="material-symbols-outlined text-[18px]" x-text="faceDone.includes(direction) ? 'check' : { right: 'arrow_back', left: 'arrow_forward', up: 'arrow_upward', down: 'arrow_downward' }[direction]"></span>
                                    <span class="font-label-sm text-label-sm capitalize" x-text="direction"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="passed.camera" x-cloak class="font-label-sm text-label-sm text-success">Face orientation check passed.</p>
                        <div class="flex items-center justify-between border-t border-[--lp-outline] pt-space-md">
                            <button type="button" @click="goTo('speed')" class="rounded-lg border border-[--lp-outline] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-ink]">Back</button>
                            <button type="button" x-show="passed.camera" x-cloak @click="goTo('screen')" class="rounded-lg bg-[--lp-primary] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]">Continue</button>
                        </div>
                    </div>

                    <div x-show="step === 'screen'" x-cloak class="space-y-space-md">
                        <p class="font-label-sm text-label-sm text-[--lp-ink]">3. Screen Capture</p>
                        <p class="font-body-sm text-body-sm text-[--lp-muted]">Your entire screen will be recorded during the exam.</p>
                        <button
                            type="button"
                            @click="shareScreen()"
                            class="rounded-lg bg-[--lp-primary] px-space-lg py-space-sm font-label-sm text-label-sm text-[--lp-on-primary]"
                        >
                            <span x-show="!passed.screen">Share Screen</span>
                            <span x-show="passed.screen" x-cloak>Retry</span>
                        </button>
                        <p x-show="passed.screen" x-cloak class="font-label-sm text-label-sm text-success">Screen sharing is active.</p>
                        <div class="flex items-center justify-between border-t border-[--lp-outline] pt-space-md">
                            <button type="button" @click="goTo('camera')" class="rounded-lg border border-[--lp-outline] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-ink]">Back</button>
                            <span x-show="passed.screen" x-cloak class="rounded-lg bg-[--lp-primary]/10 px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-primary]">Ready to start exam</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview 2: Live exam session -->
        <div>
            <p class="font-label-sm text-label-sm text-[--lp-ink]">Live exam session</p>
            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-muted]">Camera and full-screen sharing stay active for the duration &mdash; tab switches, copy/paste, and exits are logged automatically.</p>

            <div class="mt-space-sm overflow-hidden rounded-lg border border-[--lp-outline] bg-[--lp-surface]" x-data="{
                current: 0,
                answered: { 0: 0, 2: 1 },
                questions: @js($questions),
                remaining: 300,
                timer: null,
                formatted() {
                    const m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                    const s = (this.remaining % 60).toString().padStart(2, '0');
                    return m + ':' + s;
                },
                selectOption(index) { this.answered[this.current] = index; },
            }" x-init="timer = setInterval(() => { remaining = Math.max(remaining - 1, 0); }, 1000)" x-on:destroy="clearInterval(timer)">
                <div class="flex items-center justify-between border-b border-[--lp-outline] px-space-md py-space-sm">
                    <span class="inline-flex items-center gap-space-xs rounded-full bg-error/10 px-space-sm py-space-xxs font-label-sm text-label-sm text-error">Proctored</span>
                    <span class="flex items-center gap-space-xs font-label-sm text-label-sm text-[--lp-ink]">
                        <span class="material-symbols-outlined text-[16px]">timer</span>
                        <span x-text="formatted()"></span>
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-space-md p-space-md">
                    <div class="space-y-space-sm">
                        <div class="grid grid-cols-2 gap-space-sm">
                            <div class="space-y-space-xs">
                                <p class="font-label-sm text-label-sm text-[--lp-muted]">Camera</p>
                                <div class="flex aspect-video items-center justify-center rounded-lg bg-[--lp-dark]">
                                    <span class="material-symbols-outlined text-[15px] text-[--lp-on-dark]/70">videocam</span>
                                </div>
                            </div>
                            <div class="space-y-space-xs">
                                <p class="font-label-sm text-label-sm text-[--lp-muted]">Screen Share</p>
                                <div class="flex aspect-video items-center justify-center rounded-lg bg-[--lp-dark]">
                                    <span class="material-symbols-outlined text-[15px] text-[--lp-on-dark]/70">screen_share</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="mb-space-sm font-label-sm text-label-sm text-[--lp-muted]">Questions</p>
                            <div class="grid grid-cols-4 gap-space-xs">
                                @foreach ($questions as $index => $question)
                                    <button
                                        type="button"
                                        @click="current = {{ $index }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg font-label-sm text-label-sm transition-colors"
                                        :class="current === {{ $index }} ? 'bg-[--lp-primary] text-[--lp-on-primary]' : ({{ $index }} in answered ? 'bg-[--lp-primary]/10 text-[--lp-primary]' : 'bg-[--lp-bg] text-[--lp-muted] hover:text-[--lp-ink]')"
                                    >{{ $index + 1 }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-muted]">
                            Question <span x-text="current + 1"></span> of <span x-text="questions.length"></span>
                        </p>
                        <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-ink]" x-text="questions[current].text"></p>

                        <div class="mt-space-sm space-y-space-xs">
                            <template x-for="(option, index) in questions[current].options" :key="index">
                                <button
                                    type="button"
                                    @click="selectOption(index)"
                                    class="flex w-full items-center gap-space-xs rounded-lg border px-space-sm py-space-xs text-left transition-colors"
                                    :class="answered[current] === index ? 'border-[--lp-primary] bg-[--lp-primary]/5' : 'border-[--lp-outline] hover:bg-[--lp-bg]'"
                                >
                                    <span class="h-3 w-3 shrink-0 rounded-full border-2" :class="answered[current] === index ? 'border-[--lp-primary] bg-[--lp-primary]' : 'border-[--lp-outline]'"></span>
                                    <span class="font-body-sm text-body-sm text-[--lp-ink]" x-text="option"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end border-t border-[--lp-outline] px-space-md py-space-sm">
                    <button type="button" class="rounded-lg bg-[--lp-primary] px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]">Submit Exam</button>
                </div>
            </div>
        </div>
    </div>
</div>
