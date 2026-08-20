// Shared MediaPipe-based head-pose "reading suspected" detector for proctoring pages.
//
// The FaceLandmarker instance is intentionally kept in module scope, never assigned
// onto a framework's reactive state: wrapping it in a Proxy (e.g. Alpine/Vue
// reactivity) corrupts its internal WASM state (see google-ai-edge/mediapipe#5592),
// causing "Task is not initialized with video mode".
let faceLandmarker = null;

const YAW_THRESHOLD = 15;
const PITCH_THRESHOLD = 12;

// How long the head/gaze must stay off-screen (non-center) before it's flagged
// as "reading suspected" rather than a brief, incidental glance away.
const READING_SUSPECTED_MS = 2500;

// How long the face must be completely absent from frame before it's flagged
// as "no face detected" rather than a brief, incidental occlusion.
const NO_FACE_SUSPECTED_MS = 3000;

// How long the head must stay pitched down (e.g. looking at a phone/notes in
// the lap) before it's flagged, separately from the shorter generic
// "reading suspected" away-from-screen threshold above.
const FACING_DOWN_SUSPECTED_MS = 5000;

async function loadFaceLandmarker() {
    if (faceLandmarker) {
        return faceLandmarker;
    }

    const vision = await import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14');
    const filesetResolver = await vision.FilesetResolver.forVisionTasks(
        'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm'
    );
    const options = {
        baseOptions: {
            modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
            delegate: 'GPU',
        },
        outputFacialTransformationMatrixes: true,
        runningMode: 'VIDEO',
        numFaces: 1,
    };

    try {
        faceLandmarker = await vision.FaceLandmarker.createFromOptions(filesetResolver, options);
    } catch (gpuError) {
        console.warn('GPU delegate failed, falling back to CPU', gpuError);
        options.baseOptions.delegate = 'CPU';
        faceLandmarker = await vision.FaceLandmarker.createFromOptions(filesetResolver, options);
    }

    return faceLandmarker;
}

function directionFromPose(yaw, pitch) {
    if (yaw > YAW_THRESHOLD) {
        return 'left';
    }
    if (yaw < -YAW_THRESHOLD) {
        return 'right';
    }
    if (pitch > PITCH_THRESHOLD) {
        return 'up';
    }
    if (pitch < -PITCH_THRESHOLD) {
        return 'down';
    }

    return 'center';
}

/**
 * Tracks head pose from a <video> element and reports sustained off-screen
 * gaze ("reading suspected") separately from momentary glances.
 *
 * @param {object} handlers
 * @param {(pose: {yaw: number, pitch: number, direction: string, landmarks: Array}) => void} [handlers.onPose]
 * @param {(suspected: boolean) => void} [handlers.onReadingSuspectedChange]
 * @param {() => void} [handlers.onNoFace]
 * @param {(suspected: boolean) => void} [handlers.onNoFaceSuspectedChange]
 * @param {(suspected: boolean) => void} [handlers.onFacingDownSuspectedChange]
 */
export function createReadingDetector(handlers = {}) {
    let videoEl = null;
    let rafId = null;
    let running = false;
    let awayFromScreenSince = null;
    let readingSuspected = false;
    let noFaceSince = null;
    let noFaceSuspected = false;
    let facingDownSince = null;
    let facingDownSuspected = false;

    function setReadingSuspected(value) {
        if (value === readingSuspected) {
            return;
        }
        readingSuspected = value;
        handlers.onReadingSuspectedChange?.(value);
    }

    function setNoFaceSuspected(value) {
        if (value === noFaceSuspected) {
            return;
        }
        noFaceSuspected = value;
        handlers.onNoFaceSuspectedChange?.(value);
    }

    function setFacingDownSuspected(value) {
        if (value === facingDownSuspected) {
            return;
        }
        facingDownSuspected = value;
        handlers.onFacingDownSuspectedChange?.(value);
    }

    function tick() {
        if (! running) {
            return;
        }

        if (videoEl.readyState >= 2) {
            const result = faceLandmarker.detectForVideo(videoEl, performance.now());
            const landmarks = result.faceLandmarks?.[0];
            const matrix = result.facialTransformationMatrixes?.[0]?.data;

            if (! landmarks || ! matrix) {
                // No face in frame is also "not looking at the screen" (and the
                // easiest way to evade the pose check below), so it counts
                // toward the same away-from-screen timer.
                if (awayFromScreenSince === null) {
                    awayFromScreenSince = performance.now();
                }
                setReadingSuspected((performance.now() - awayFromScreenSince) >= READING_SUSPECTED_MS);

                if (noFaceSince === null) {
                    noFaceSince = performance.now();
                }
                setNoFaceSuspected((performance.now() - noFaceSince) >= NO_FACE_SUSPECTED_MS);

                // A steep enough downward tilt (e.g. looking at a phone/notes
                // in the lap) typically makes MediaPipe lose face tracking
                // entirely rather than reporting a "down" pose, so a
                // sustained no-face reading also counts toward the
                // facing-down timer instead of resetting it.
                if (facingDownSince === null) {
                    facingDownSince = performance.now();
                }
                setFacingDownSuspected((performance.now() - facingDownSince) >= FACING_DOWN_SUSPECTED_MS);
                handlers.onNoFace?.();
            } else {
                noFaceSince = null;
                setNoFaceSuspected(false);

                const yawRad = Math.atan2(-matrix[8], Math.sqrt(matrix[0] ** 2 + matrix[4] ** 2));
                const pitchRad = Math.atan2(matrix[9], matrix[10]);
                const yaw = -yawRad * (180 / Math.PI);
                const pitch = pitchRad * (180 / Math.PI);
                const direction = directionFromPose(yaw, pitch);

                if (direction === 'center' || direction === 'down') {
                    awayFromScreenSince = null;
                    setReadingSuspected(false);
                } else {
                    if (awayFromScreenSince === null) {
                        awayFromScreenSince = performance.now();
                    }
                    setReadingSuspected((performance.now() - awayFromScreenSince) >= READING_SUSPECTED_MS);
                }

                // "Down" gets its own, longer-lived timer/event rather than
                // feeding the generic reading-suspected one above, since a
                // brief downward glance (e.g. at the keyboard) is normal
                // during a typed exam and shouldn't be flagged as quickly.
                if (direction === 'down') {
                    if (facingDownSince === null) {
                        facingDownSince = performance.now();
                    }
                    setFacingDownSuspected((performance.now() - facingDownSince) >= FACING_DOWN_SUSPECTED_MS);
                } else {
                    facingDownSince = null;
                    setFacingDownSuspected(false);
                }

                handlers.onPose?.({ yaw, pitch, direction, landmarks });
            }
        }

        rafId = requestAnimationFrame(tick);
    }

    return {
        async start(video) {
            videoEl = video;
            await loadFaceLandmarker();
            running = true;
            awayFromScreenSince = null;
            readingSuspected = false;
            noFaceSince = null;
            noFaceSuspected = false;
            facingDownSince = null;
            facingDownSuspected = false;
            tick();
        },
        stop() {
            running = false;
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
        },
    };
}
