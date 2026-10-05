import Sortable from 'sortablejs';

document.querySelectorAll('[data-multi-select]').forEach((root) => {
    const max = parseInt(root.dataset.max || '0', 10);
    const inputs = () => [...root.querySelectorAll('input[type=checkbox]')];

    root.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLInputElement) || event.target.type !== 'checkbox') {
            return;
        }
        const checked = inputs().filter((i) => i.checked);
        if (max > 0 && checked.length > max) {
            event.target.checked = false;
            root.dispatchEvent(
                new CustomEvent('multi-limit', { detail: { max }, bubbles: true }),
            );
        }
    });
});

document.addEventListener('multi-limit', (event) => {
    const max = event.detail?.max;
    const msg = document.querySelector('[data-multi-limit-msg]');
    if (msg && max) {
        msg.textContent = `You can select at most ${max} options.`;
        msg.hidden = false;
    }
});

function initRankLists() {
    document.querySelectorAll('[data-rank-list]').forEach((list) => {
        const name = list.dataset.rankName;
        const input = document.querySelector(`[data-rank-input][name="${name}"]`);
        if (!input) {
            return;
        }

        const sync = () => {
            const values = [...list.querySelectorAll('[data-rank-value]')].map(
                (li) => li.dataset.rankValue,
            );
            input.value = JSON.stringify(values);
        };

        sync();

        Sortable.create(list, {
            handle: '.drag-handle',
            animation: 150,
            onSort: sync,
        });
    });
}

function initRangeOutputs() {
    document.querySelectorAll('[data-range-output]').forEach((range) => {
        const key = range.dataset.rangeOutput;
        const display = document.querySelector(`[data-range-display="${key}"]`);
        const update = () => {
            if (display) {
                display.textContent = range.value;
            }
        };
        range.addEventListener('input', update);
        update();
    });
}

function initSignaturePads() {
    document.querySelectorAll('[data-signature-field]').forEach((root) => {
        const canvas = root.querySelector('[data-signature-canvas]');
        const hidden = root.querySelector('[data-signature-drawn-input]');
        const fileInput = root.querySelector('[data-signature-file]');
        if (!canvas || !hidden) {
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        const resize = () => {
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;
            canvas.width = Math.floor(rect.width * ratio);
            canvas.height = Math.floor(rect.height * ratio);
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.strokeStyle = '#1a1a1a';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        };
        resize();
        window.addEventListener('resize', resize);

        let drawing = false;
        let hasInk = false;

        const pos = (event) => {
            const rect = canvas.getBoundingClientRect();
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clientY = event.touches ? event.touches[0].clientY : event.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        };

        const start = (event) => {
            event.preventDefault();
            drawing = true;
            const p = pos(event);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        };

        const move = (event) => {
            if (!drawing) {
                return;
            }
            event.preventDefault();
            const p = pos(event);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            hasInk = true;
        };

        const end = () => {
            drawing = false;
            if (hasInk) {
                hidden.value = canvas.toDataURL('image/png');
                if (fileInput) {
                    fileInput.value = '';
                }
            }
        };

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        root.querySelector('[data-signature-clear]')?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hidden.value = '';
            hasInk = false;
        });

        fileInput?.addEventListener('change', () => {
            if (fileInput.files?.length) {
                hidden.value = '';
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasInk = false;
            }
        });
    });
}

function initMetadataFields() {
    const payload = JSON.stringify({
        captured_at: new Date().toISOString(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        language: navigator.language,
        user_agent: navigator.userAgent,
        channel: 'web',
    });
    document.querySelectorAll('[data-metadata-field]').forEach((input) => {
        if (!input.value) {
            input.value = payload;
        }
    });
}

function fieldNumericValue(form, fieldName) {
    const el = form.querySelector(`[name="${fieldName}"]`);
    if (!el) {
        return 0;
    }
    const num = parseFloat(el.value);
    return Number.isFinite(num) ? num : 0;
}

function initCalculateFields() {
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    if (!form) {
        return;
    }

    const outputs = [...form.querySelectorAll('[data-calculate-output]')];
    if (!outputs.length) {
        return;
    }

    const recalc = () => {
        outputs.forEach((output) => {
            const expr = (output.dataset.calculateExpr || '').trim();
            if (!expr) {
                return;
            }
            let safe = expr.replace(/\${([^}]+)}/g, (_, name) => {
                const trimmed = name.trim();
                return String(fieldNumericValue(form, trimmed));
            });
            safe = safe.replace(/[a-zA-Z_][a-zA-Z0-9_]*/g, (name) => {
                if (/^\d/.test(name)) {
                    return name;
                }
                return String(fieldNumericValue(form, name));
            });
            if (!/^[\d\s+\-*/().]+$/.test(safe)) {
                return;
            }
            try {
                // eslint-disable-next-line no-new-func
                const value = Function(`"use strict"; return (${safe});`)();
                if (Number.isFinite(value)) {
                    output.value = String(Math.round(value * 1000) / 1000);
                }
            } catch {
                /* ignore invalid expressions */
            }
        });
    };

    form.addEventListener('input', recalc);
    form.addEventListener('change', recalc);
    recalc();
}

async function initBarcodeScanners() {
    if (!('BarcodeDetector' in window)) {
        return;
    }

    document.querySelectorAll('[data-barcode-field]').forEach((root) => {
        const input = root.querySelector('input[type=text]');
        const btn = root.querySelector('[data-barcode-scan]');
        const status = root.querySelector('[data-barcode-status]');
        if (!input || !btn) {
            return;
        }

        btn.addEventListener('click', async () => {
            status.hidden = false;
            status.textContent = 'Starting camera…';
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                });
                const video = document.createElement('video');
                video.srcObject = stream;
                video.setAttribute('playsinline', 'true');
                await video.play();

                const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'qr_code'] });
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                const stop = () => stream.getTracks().forEach((t) => t.stop());

                const tick = async () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0);
                    const codes = await detector.detect(canvas);
                    if (codes.length) {
                        input.value = codes[0].rawValue;
                        status.textContent = 'Barcode captured.';
                        stop();
                        return;
                    }
                    requestAnimationFrame(tick);
                };

                status.textContent = 'Point the camera at a barcode (scanning…)';
                tick();
            } catch {
                status.textContent = 'Camera scan unavailable; type the code manually.';
            }
        });
    });
}

initRankLists();
initRangeOutputs();
initSignaturePads();
initMetadataFields();
initCalculateFields();
initBarcodeScanners();

document.querySelector('form[enctype="multipart/form-data"]')?.addEventListener('submit', () => {
    document.querySelectorAll('[data-rank-list]').forEach((list) => {
        const name = list.dataset.rankName;
        const input = document.querySelector(`[data-rank-input][name="${name}"]`);
        if (!input) {
            return;
        }
        const values = [...list.querySelectorAll('[data-rank-value]')].map(
            (li) => li.dataset.rankValue,
        );
        input.value = JSON.stringify(values);
    });
});
