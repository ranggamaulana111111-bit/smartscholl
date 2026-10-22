const DUPLICATE_WINDOW_MS = 2500;
const SEEN_PRUNE_MS = 10000;
const FALLBACK_FRAME_MS = 120;
const FALLBACK_MAX_EDGE = 640;

const CAMERA_MESSAGES = {
    NotAllowedError: 'Izin kamera ditolak. Izinkan kamera di pengaturan situs, lalu tekan Mulai Kamera lagi.',
    PermissionDeniedError: 'Izin kamera ditolak. Izinkan kamera di pengaturan situs, lalu tekan Mulai Kamera lagi.',
    SecurityError: 'Kamera hanya aktif di koneksi aman (HTTPS) atau di localhost.',
    NotFoundError: 'Tidak ada kamera yang terdeteksi di perangkat ini.',
    DevicesNotFoundError: 'Tidak ada kamera yang terdeteksi di perangkat ini.',
    NotReadableError: 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi.',
    AbortError: 'Kamera dihentikan sebelum siap. Coba tekan Mulai Kamera sekali lagi.',
    OverconstrainedError: 'Kamera ini menolak resolusi yang diminta. Kamera lain akan dicoba.',
};

/**
 * Pembaca QR lewat kamera belakang.
 *
 * Memakai BarcodeDetector native bila browser menyediakannya (Chrome Android,
 * Edge, Chrome desktop), lalu jatuh ke jsQR yang dimuat dinamis untuk Safari
 * dan browser lama. Modul viewfinder tetap milik kita; pustaka hanya
 * mendecode frame.
 */
export function createQrScanner({ video, onResult, onState }) {
    let stream = null;
    let detector = null;
    let jsQr = null;
    let canvas = null;
    let context = null;
    let timer = null;
    let running = false;
    let starting = false;
    const seen = new Map();

    const emit = (name, detail = null) => {
        if (typeof onState === 'function') {
            onState(name, detail);
        }
    };

    const schedule = (task, delay) => {
        timer = setTimeout(task, delay);
    };

    const handleCameraFailure = (error) => {
        const name = error?.name ?? '';
        stop();

        if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'SecurityError') {
            emit('denied', CAMERA_MESSAGES[name] ?? CAMERA_MESSAGES.NotAllowedError);
            return;
        }

        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            emit('nocamera', CAMERA_MESSAGES[name]);
            return;
        }

        emit('error', CAMERA_MESSAGES[name] ?? 'Kamera gagal dibuka. Periksa izin kamera pada perangkat ini.');
    };

    const buildNativeDetector = async () => {
        if (!('BarcodeDetector' in window)) {
            return null;
        }

        try {
            const formats = await window.BarcodeDetector.getSupportedFormats();

            if (!formats.includes('qr_code')) {
                return null;
            }

            return new window.BarcodeDetector({ formats: ['qr_code'] });
        } catch {
            return null;
        }
    };

    const loadFallbackDecoder = async () => {
        if (jsQr) {
            return jsQr;
        }

        const module = await import('jsqr');

        jsQr = module.default;

        return jsQr;
    };

    const accept = (value) => {
        const payload = String(value ?? '').trim();

        if (payload === '') {
            return;
        }

        const now = performance.now();
        const previous = seen.get(payload);

        if (previous !== undefined && now - previous < DUPLICATE_WINDOW_MS) {
            return;
        }

        seen.set(payload, now);

        for (const [key, stamp] of seen) {
            if (now - stamp > SEEN_PRUNE_MS) {
                seen.delete(key);
            }
        }

        onResult(payload);
    };

    const scanWithNativeDetector = async () => {
        if (!running) {
            return;
        }

        try {
            const codes = await detector.detect(video);

            if (!running) {
                return;
            }

            if (codes.length > 0) {
                accept(codes[0].rawValue);
            }
        } catch {
            // Frame yang gagal di-decode dilewati, pemindaian dilanjutkan.
        }

        schedule(scanWithNativeDetector, 0);
    };

    const scanWithFallbackDecoder = () => {
        if (!running) {
            return;
        }

        if (video.readyState >= 2 && video.videoWidth > 0 && video.videoHeight > 0) {
            const scale = Math.min(1, FALLBACK_MAX_EDGE / Math.max(video.videoWidth, video.videoHeight));

            if (!canvas) {
                canvas = document.createElement('canvas');
                context = canvas.getContext('2d', { willReadFrequently: true });
            }

            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const frame = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQr(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' });

            if (code?.data) {
                accept(code.data);
            }
        }

        schedule(scanWithFallbackDecoder, FALLBACK_FRAME_MS);
    };

    const requestStream = async () => {
        try {
            return await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            });
        } catch (error) {
            if (error?.name !== 'OverconstrainedError') {
                throw error;
            }

            return navigator.mediaDevices.getUserMedia({ audio: false, video: true });
        }
    };

    const start = async () => {
        if (running || starting) {
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia || !window.isSecureContext) {
            emit('unsupported', CAMERA_MESSAGES.SecurityError);
            return;
        }

        starting = true;
        emit('starting');

        try {
            detector = await buildNativeDetector();

            if (!detector) {
                await loadFallbackDecoder();
            }

            stream = await requestStream();

            video.srcObject = stream;
            video.setAttribute('playsinline', '');
            video.muted = true;

            await video.play();
        } catch (error) {
            starting = false;
            handleCameraFailure(error);
            return;
        }

        starting = false;
        running = true;
        emit('scanning');

        schedule(detector ? scanWithNativeDetector : scanWithFallbackDecoder, 0);
    };

    const stop = () => {
        running = false;

        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }

        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }

        video.srcObject = null;
        emit('idle');
    };

    return {
        start,
        stop,
        isRunning: () => running,
        toggle: () => (running ? stop() : start()),
    };
}
