import SignaturePad from 'signature_pad';

/**
 * @param {HTMLFormElement} form
 * @returns {'draw' | 'upload'}
 */
function currentMethod(form) {
    const checked = form.querySelector('input[name="answers[head_organisation_signature_method]"]:checked');
    if (checked instanceof HTMLInputElement && checked.value === 'upload') {
        return 'upload';
    }
    return 'draw';
}

/**
 * Fills the hidden PNG data URL before any POST (submit or auto-save fetch FormData).
 *
 * @param {HTMLFormElement} form
 */
export function syncApplicantHeadSignatureTransportFields(form) {
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    const root = form.querySelector('[data-applicant-head-signature]');
    if (!root) {
        return;
    }
    const hidden = root.querySelector('[data-applicant-signature-canvas-png]');
    const canvas = root.querySelector('[data-applicant-signature-canvas]');
    if (!(hidden instanceof HTMLInputElement) || !(canvas instanceof HTMLCanvasElement)) {
        return;
    }

    const pad = /** @type {SignaturePad | undefined} */ (canvas._wffSignaturePad);
    if (currentMethod(form) === 'draw' && pad && !pad.isEmpty()) {
        hidden.value = pad.toDataURL('image/png');
    } else {
        hidden.value = '';
    }
}

/**
 * @param {HTMLFormElement} form
 */
export function initApplicantHeadSignature(form) {
    const root = form.querySelector('[data-applicant-head-signature]');
    if (!root) {
        return;
    }

    const canvas = root.querySelector('[data-applicant-signature-canvas]');
    const clearBtn = root.querySelector('[data-applicant-signature-clear]');
    const hidden = root.querySelector('[data-applicant-signature-canvas-png]');
    const fileInput = root.querySelector('input[type="file"][name="answers[head_organisation_signature_image]"]');

    if (
        !(canvas instanceof HTMLCanvasElement) ||
        !(hidden instanceof HTMLInputElement) ||
        !(fileInput instanceof HTMLInputElement)
    ) {
        return;
    }

    const signaturePad = new SignaturePad(canvas, {
        penColor: '#111827',
        minWidth: 1.2,
        maxWidth: 2.4,
        backgroundColor: 'rgb(255,255,255)',
    });
    canvas._wffSignaturePad = signaturePad;
    canvas.style.touchAction = 'none';
    canvas.style.cursor = 'crosshair';

    function resizeCanvasAndReset() {
        const wrap = canvas.parentElement;
        const cssW = Math.max(280, Math.floor(wrap?.clientWidth ?? 600));
        const cssH = 160;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.style.width = `${cssW}px`;
        canvas.style.height = `${cssH}px`;
        canvas.width = Math.floor(cssW * ratio);
        canvas.height = Math.floor(cssH * ratio);
        const ctx = canvas.getContext('2d');
        ctx?.setTransform(ratio, 0, 0, ratio, 0, 0);
        signaturePad.clear();
    }

    function loadExistingSignatureIfAny() {
        const url = root.getAttribute('data-applicant-signature-existing-src');
        if (!url || currentMethod(form) !== 'draw') {
            return;
        }
        const img = new Image();
        img.onload = () => {
            if (currentMethod(form) !== 'draw') {
                return;
            }
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        };
        img.src = url;
    }

    resizeCanvasAndReset();
    loadExistingSignatureIfAny();

    window.addEventListener('resize', () => {
        const hadInk = !signaturePad.isEmpty();
        resizeCanvasAndReset();
        if (!hadInk) {
            loadExistingSignatureIfAny();
        }
        syncApplicantHeadSignatureTransportFields(form);
    });

    clearBtn?.addEventListener('click', () => {
        signaturePad.clear();
        hidden.value = '';
    });

    form.querySelectorAll('input[data-applicant-signature-method]').forEach((r) => {
        r.addEventListener('change', () => {
            const method = currentMethod(form);
            if (method === 'upload') {
                signaturePad.clear();
                hidden.value = '';
            } else {
                fileInput.value = '';
                // Keep user intent clear: draw mode starts from fresh pad unless there is saved server signature.
                signaturePad.clear();
                loadExistingSignatureIfAny();
            }
        });
    });

    form.addEventListener(
        'submit',
        () => {
            syncApplicantHeadSignatureTransportFields(form);
            if (currentMethod(form) !== 'draw') {
                hidden.value = '';
            }
        },
        { capture: true },
    );
}
