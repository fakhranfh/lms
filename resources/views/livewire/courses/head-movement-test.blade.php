@section('title', 'Head Movement Detection Test')

<div
    class="space-y-space-lg px-gutter py-space-lg"
    x-data="headMovementTest()"
    x-init="init()"
>
    <div>
        <h1 class="font-heading-lg text-heading-lg text-on-surface">Head Movement Detection (Test)</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">
            Experimental page for proctoring R&amp;D. Not wired into any real assessment flow.
        </p>
    </div>

    <template x-if="loadError">
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg">
            <p class="font-body-md text-body-md text-error" x-text="loadError"></p>
        </div>
    </template>

    <div class="flex flex-wrap gap-space-lg">
        <div class="relative w-[480px] max-w-full aspect-video bg-surface-container rounded-lg overflow-hidden">
            <video x-ref="video" class="w-full h-full object-cover -scale-x-100" autoplay playsinline muted></video>
            <canvas x-ref="overlay" class="absolute inset-0 w-full h-full -scale-x-100"></canvas>
        </div>

        <div class="flex-1 min-w-[240px] space-y-space-md">
            <div
                class="px-gutter py-space-md rounded-lg space-y-space-sm"
                :class="readingSuspected ? 'bg-error/10 border border-error/40' : 'bg-surface-container'"
            >
                <p class="font-label-md text-label-md text-on-surface-variant">Status</p>
                <p
                    class="font-heading-md text-heading-md"
                    :class="readingSuspected ? 'text-error' : 'text-on-surface'"
                    x-text="status"
                ></p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Yaw: <span x-text="yaw.toFixed(1)"></span>&deg;
                    &nbsp;&middot;&nbsp;
                    Pitch: <span x-text="pitch.toFixed(1)"></span>&deg;
                </p>
            </div>

            <div class="flex gap-space-sm">
                <button
                    type="button"
                    @click="start()"
                    :disabled="running || loading"
                    class="px-space-md py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md disabled:opacity-50"
                >
                    <span x-show="!loading" x-text="running ? 'Running…' : 'Start camera'"></span>
                    <span x-show="loading">Loading model…</span>
                </button>
                <button
                    type="button"
                    @click="stop()"
                    :disabled="!running"
                    class="px-space-md py-space-sm rounded-lg border border-outline text-on-surface font-label-md text-label-md disabled:opacity-50"
                >
                    Stop
                </button>
            </div>

            <div class="px-gutter py-space-md bg-surface-container rounded-lg">
                <p class="font-label-md text-label-md text-on-surface-variant mb-space-sm">Event log</p>
                <ul class="space-y-1 max-h-64 overflow-y-auto font-body-sm text-body-sm text-on-surface-variant" x-ref="log"></ul>
            </div>
        </div>
    </div>
</div>
