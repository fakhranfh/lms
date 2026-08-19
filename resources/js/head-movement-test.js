// Kept outside Alpine's reactive state: wrapping the WASM-backed FaceLandmarker
// instance in a reactive Proxy corrupts its internal state (see
// google-ai-edge/mediapipe#5592), causing "Task is not initialized with video mode".
let faceLandmarker = null;

// How long the head/gaze must stay off-screen (non-center) before it's flagged
// as "reading suspected" rather than a brief, incidental glance away.
const READING_SUSPECTED_MS = 2500;

export default () => ({
    loading: false,
    running: false,
    loadError: null,
    status: 'Idle',
    yaw: 0,
    pitch: 0,
    stream: null,
    rafId: null,
    lastDirection: 'center',
    readingSuspected: false,
    awayFromScreenSince: null,

    init() {
        // Model loads lazily on first "Start camera" click.
    },

    log(message) {
        const el = this.$refs.log;
        const item = document.createElement('li');
        item.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        el.prepend(item);
        while (el.children.length > 50) {
            el.removeChild(el.lastChild);
        }
    },

    async start() {
        if (this.running || this.loading) {
            return;
        }

        this.loadError = null;
        this.loading = true;

        try {
            if (! faceLandmarker) {
                const vision = await import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14');
                const filesetResolver = await vision.FilesetResolver.forVisionTasks(
                    'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm'
                );
                const landmarkerOptions = {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
                        delegate: 'GPU',
                    },
                    outputFacialTransformationMatrixes: true,
                    runningMode: 'VIDEO',
                    numFaces: 1,
                };

                try {
                    faceLandmarker = await vision.FaceLandmarker.createFromOptions(filesetResolver, landmarkerOptions);
                } catch (gpuError) {
                    console.warn('GPU delegate failed, falling back to CPU', gpuError);
                    landmarkerOptions.baseOptions.delegate = 'CPU';
                    faceLandmarker = await vision.FaceLandmarker.createFromOptions(filesetResolver, landmarkerOptions);
                }
            }

            this.stream = await navigator.mediaDevices.getUserMedia({ video: { width: 480, height: 360 } });
            this.$refs.video.srcObject = this.stream;
            await this.$refs.video.play();

            this.running = true;
            this.status = 'Tracking…';
            this.log('Camera started, model ready.');
            this.loop();
        } catch (e) {
            console.error('Head movement test init failed', e);
            this.loadError = 'Failed to start camera or load the face model: ' + e.message;
            this.status = 'Error';
        } finally {
            this.loading = false;
        }
    },

    stop() {
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
        this.running = false;
        this.status = 'Idle';
        this.log('Camera stopped.');
    },

    loop() {
        if (! this.running) {
            return;
        }

        const video = this.$refs.video;
        if (video.readyState >= 2) {
            const result = faceLandmarker.detectForVideo(video, performance.now());
            this.render(result);
        }

        this.rafId = requestAnimationFrame(() => this.loop());
    },

    render(result) {
        const canvas = this.$refs.overlay;
        canvas.width = this.$refs.video.videoWidth;
        canvas.height = this.$refs.video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (! result.faceLandmarks?.length) {
            this.status = 'No face detected';
            this.setDirection('no_face');
            this.awayFromScreenSince = null;
            this.readingSuspected = false;
            return;
        }

        const matrix = result.facialTransformationMatrixes?.[0]?.data;
        if (! matrix) {
            this.status = 'Tracking…';
            return;
        }

        // Extract yaw/pitch from the 4x4 rotation matrix (column-major).
        const yawRad = Math.atan2(-matrix[8], Math.sqrt(matrix[0] ** 2 + matrix[4] ** 2));
        const pitchRad = Math.atan2(matrix[9], matrix[10]);
        this.yaw = -yawRad * (180 / Math.PI);
        this.pitch = pitchRad * (180 / Math.PI);

        const landmarks = result.faceLandmarks[0];
        ctx.fillStyle = '#22c55e';
        for (const point of landmarks) {
            ctx.fillRect(point.x * canvas.width, point.y * canvas.height, 1.5, 1.5);
        }

        const yawThreshold = 15;
        const pitchThreshold = 12;

        let direction = 'center';
        if (this.yaw > yawThreshold) {
            direction = 'left';
        } else if (this.yaw < -yawThreshold) {
            direction = 'right';
        } else if (this.pitch > pitchThreshold) {
            direction = 'up';
        } else if (this.pitch < -pitchThreshold) {
            direction = 'down';
        }

        const wasReadingSuspected = this.readingSuspected;

        if (direction === 'center') {
            this.awayFromScreenSince = null;
            this.readingSuspected = false;
        } else {
            if (this.awayFromScreenSince === null) {
                this.awayFromScreenSince = performance.now();
            }
            this.readingSuspected = (performance.now() - this.awayFromScreenSince) >= READING_SUSPECTED_MS;
        }

        if (this.readingSuspected && ! wasReadingSuspected) {
            this.log('Reading suspected: gaze held away from screen for ' + (READING_SUSPECTED_MS / 1000) + 's+');
        } else if (! this.readingSuspected && wasReadingSuspected) {
            this.log('Reading suspected cleared.');
        }

        this.status = this.readingSuspected
            ? 'Kemungkinan sedang membaca (mata tidak menatap layar)'
            : {
                center: 'Looking at screen',
                left: 'Turned left',
                right: 'Turned right',
                up: 'Looking up',
                down: 'Looking down',
            }[direction];

        this.setDirection(direction);
    },

    setDirection(direction) {
        if (direction === this.lastDirection) {
            return;
        }
        this.lastDirection = direction;
        this.log('Direction changed: ' + direction);
    },
});
