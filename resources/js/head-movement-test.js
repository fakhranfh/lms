import { createReadingDetector } from './reading-detector';

const STATUS_LABELS = {
    center: 'Looking at screen',
    left: 'Turned left',
    right: 'Turned right',
    up: 'Looking up',
    down: 'Looking down',
};

export default () => ({
    loading: false,
    running: false,
    loadError: null,
    status: 'Idle',
    yaw: 0,
    pitch: 0,
    readingSuspected: false,
    stream: null,
    detector: null,

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
            this.stream = await navigator.mediaDevices.getUserMedia({ video: { width: 480, height: 360 } });
            this.$refs.video.srcObject = this.stream;
            await this.$refs.video.play();

            this.detector = createReadingDetector({
                onPose: (pose) => this.renderPose(pose),
                onNoFace: () => {
                    this.status = this.readingSuspected
                        ? 'Kemungkinan sedang membaca (wajah tidak terlihat kamera)'
                        : 'No face detected';
                },
                onReadingSuspectedChange: (suspected) => {
                    this.readingSuspected = suspected;
                    this.log(suspected ? 'Reading suspected: gaze held away from screen.' : 'Reading suspected cleared.');
                },
            });
            await this.detector.start(this.$refs.video);

            this.running = true;
            this.status = 'Tracking…';
            this.log('Camera started, model ready.');
        } catch (e) {
            console.error('Head movement test init failed', e);
            this.loadError = 'Failed to start camera or load the face model: ' + e.message;
            this.status = 'Error';
        } finally {
            this.loading = false;
        }
    },

    stop() {
        this.detector?.stop();
        this.detector = null;
        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }
        this.running = false;
        this.status = 'Idle';
        this.log('Camera stopped.');
    },

    renderPose({ yaw, pitch, direction, landmarks }) {
        this.yaw = yaw;
        this.pitch = pitch;
        this.status = this.readingSuspected ? 'Kemungkinan sedang membaca (mata tidak menatap layar)' : STATUS_LABELS[direction];

        const canvas = this.$refs.overlay;
        canvas.width = this.$refs.video.videoWidth;
        canvas.height = this.$refs.video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#22c55e';
        for (const point of landmarks) {
            ctx.fillRect(point.x * canvas.width, point.y * canvas.height, 1.5, 1.5);
        }
    },
});
