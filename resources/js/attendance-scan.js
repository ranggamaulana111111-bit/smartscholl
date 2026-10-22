import { createQrScanner } from './qr-scanner';

const STATUS_LABELS = {
    hadir: 'Hadir',
    sakit: 'Sakit',
    izin: 'Izin',
    alpha: 'Alpa',
};

const STATE_MESSAGES = {
    starting: 'Menyiapkan kamera...',
    scanning: 'Kamera aktif. Arahkan ke kartu QR siswa.',
    denied: 'Kamera tidak diizinkan.',
    unsupported: 'Kamera tidak didukung perangkat ini.',
    nocamera: 'Kamera tidak ditemukan.',
    error: 'Kamera bermasalah.',
};

const STATE_TONES = {
    starting: 'text-text-muted',
    scanning: 'text-text-muted',
    denied: 'text-danger',
    unsupported: 'text-danger',
    nocamera: 'text-danger',
    error: 'text-danger',
};

/**
 * Halaman absensi pelajaran per jadwal: kamera depan (scan QR), input manual
 * sebagai jalur yang selalu bisa dipakai, dan roster rombel yang mengikuti
 * pencatatan terakhir dari server.
 */
const initLessonScan = () => {
    const root = document.querySelector('[data-lesson-scan]');

    if (!root) {
        return;
    }

    const video = root.querySelector('[data-scan-video]');
    const toggle = root.querySelector('[data-scan-toggle]');
    const overlay = root.querySelector('[data-scan-overlay]');
    const stateBox = root.querySelector('[data-scan-state]');
    const feedback = root.querySelector('[data-scan-feedback]');
    const manualForm = root.querySelector('[data-scan-manual]');
    const manualInput = root.querySelector('[data-scan-payload]');
    const rosterBody = root.querySelector('[data-scan-roster]');
    const remainingForm = root.querySelector('[data-scan-remaining-form]');
    const statusNote = root.querySelector('[data-scan-status-note]');

    const recordUrl = root.dataset.recordUrl;
    const statusUrl = root.dataset.statusUrl;
    const csrf = root.dataset.csrf;
    const pollMs = Number(root.dataset.pollMs) || 8000;

    let busy = false;
    let pollTimer = null;
    let failedPolls = 0;

    const scanner = createQrScanner({
        video,
        onResult: (payload) => {
            record(payload);
        },
        onState: (name, detail) => {
            renderScannerState(name, detail);
        },
    });

    const showFeedback = (tone, message) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.className = `scan-feedback scan-feedback--${tone}`;
        feedback.hidden = false;
    };

    const renderScannerState = (name, detail) => {
        const active = name === 'scanning' || name === 'starting';

        if (toggle) {
            toggle.textContent = active ? 'Matikan Kamera' : 'Mulai Kamera';
            toggle.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        if (video) {
            video.dataset.active = name === 'scanning' ? 'true' : 'false';
        }

        if (overlay) {
            overlay.hidden = active;
        }

        if (!stateBox) {
            setIntervalPolling(name === 'scanning');

            return;
        }

        if (name === 'idle') {
            stateBox.textContent = '';
            stateBox.hidden = true;
            setIntervalPolling(false);

            return;
        }

        stateBox.textContent = detail || STATE_MESSAGES[name] || '';
        stateBox.className = `text-xs sm:flex-1 ${STATE_TONES[name] || 'text-text-muted'}`;
        stateBox.hidden = false;
        setIntervalPolling(name === 'scanning');
    };

    const setIntervalPolling = (enabled) => {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }

        if (enabled) {
            pollTimer = setInterval(refresh, pollMs);
        }
    };

    const setCounter = (key, value) => {
        const node = root.querySelector(`[data-scan-count="${key}"]`);

        if (node) {
            node.textContent = value;
        }
    };

    const renderRoster = (rows) => {
        if (!rosterBody) {
            return;
        }

        rosterBody.replaceChildren();

        rows.forEach((row) => {
            const tr = document.createElement('tr');
            tr.dataset.studentId = String(row.id);

            const nameCell = document.createElement('td');
            const name = document.createElement('span');
            name.className = 'font-medium text-text';
            name.textContent = row.name;

            const nisn = document.createElement('span');
            nisn.className = 'block text-xs font-normal text-text-muted sm:hidden';
            nisn.textContent = row.nisn;

            nameCell.append(name, nisn);

            const nisnCell = document.createElement('td');
            nisnCell.className = 'text-text-muted hidden sm:table-cell';
            nisnCell.textContent = row.nisn;

            const statusCell = document.createElement('td');
            const chip = document.createElement('span');
            chip.className = row.status
                ? `badge badge-${row.status === 'hadir' ? 'success' : (row.status === 'alpha' ? 'danger' : 'warning')}`
                : 'badge badge-pending';
            chip.textContent = row.status ? STATUS_LABELS[row.status] : 'Belum';
            statusCell.append(chip);

            if (row.time) {
                const time = document.createElement('span');
                time.className = 'block text-xs text-text-muted mt-1';
                time.textContent = row.time;
                statusCell.append(time);
            }

            tr.append(nameCell, nisnCell, statusCell);
            rosterBody.append(tr);
        });
    };

    const applyPayload = (payload) => {
        renderRoster(payload.roster);

        Object.entries(payload.summary).forEach(([key, value]) => {
            setCounter(key, value);
        });

        if (remainingForm) {
            remainingForm.hidden = payload.summary.remaining === 0;
        }
    };

    const refresh = async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            failedPolls = 0;

            if (statusNote) {
                statusNote.hidden = true;
            }

            applyPayload(await response.json());
        } catch {
            failedPolls += 1;

            if (statusNote && failedPolls >= 2) {
                statusNote.textContent = 'Daftar siswa gagal dimuat. Koneksi akan dicoba lagi.';
                statusNote.hidden = false;
            }
        }
    };

    const record = async (payload) => {
        if (busy) {
            return;
        }

        busy = true;

        try {
            const response = await fetch(recordUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    mode: 'lesson',
                    schedule_id: root.dataset.scheduleId,
                    payload,
                }),
            });

            const result = await response.json();

            if (result.ok) {
                showFeedback(result.info ? 'info' : 'success', result.message);
            } else {
                showFeedback('error', result.message);
            }
        } catch {
            showFeedback('error', 'Kode tidak dapat dikirim. Periksa koneksi, lalu pindai ulang.');
        } finally {
            busy = false;
            if (manualInput) {
                manualInput.value = '';
                manualInput.focus();
            }
            await refresh();
        }
    };

    if (toggle) {
        toggle.addEventListener('click', () => {
            scanner.toggle();
        });
    }

    if (manualForm) {
        manualForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const payload = manualInput?.value.trim() ?? '';

            if (payload === '') {
                showFeedback('error', 'Kode QR atau kartu RFID belum diisi.');
                manualInput?.focus();

                return;
            }

            record(payload);
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden && scanner.isRunning()) {
            scanner.stop();
        }
    });

    renderScannerState('idle');
};

document.addEventListener('DOMContentLoaded', initLessonScan);
