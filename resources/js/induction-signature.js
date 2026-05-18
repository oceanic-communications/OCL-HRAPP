import SignaturePad from 'signature_pad';

/**
 * @param {HTMLFormElement} form
 */
function syncInductionSignature(form) {
    const root = form.querySelector('[data-induction-signature]');
    if (!(root instanceof HTMLElement)) {
        return;
    }
    const canvas = root.querySelector('[data-induction-signature-canvas]');
    const hidden = root.querySelector('[data-induction-signature-output]');
    if (!(canvas instanceof HTMLCanvasElement) || !(hidden instanceof HTMLInputElement)) {
        return;
    }
    const pad = /** @type {SignaturePad | undefined} */ (canvas._inductionSignaturePad);
    if (pad && !pad.isEmpty()) {
        hidden.value = pad.toDataURL('image/png');
    } else {
        hidden.value = '';
    }
}

/**
 * @param {HTMLFormElement} form
 */
function updateInductionSubmitState(form) {
    const submitBtn = form.querySelector('[data-induction-submit]');
    const checkbox = form.querySelector('[data-induction-acknowledge]');
    const canvas = form.querySelector('[data-induction-signature-canvas]');
    const questions = Array.from(form.querySelectorAll('[data-induction-question]'));

    const acked = checkbox instanceof HTMLInputElement && checkbox.checked;
    const pad = canvas instanceof HTMLCanvasElement ? canvas._inductionSignaturePad : undefined;
    const signed = pad instanceof Object && 'isEmpty' in pad && typeof pad.isEmpty === 'function' && !pad.isEmpty();
    const questionsAnswered = questions.every((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return true;
        }
        return input.value.trim().length > 0;
    });

    if (submitBtn instanceof HTMLButtonElement) {
        submitBtn.disabled = !(acked && signed && questionsAnswered);
    }
}

/**
 * @param {HTMLFormElement} form
 */
export function initInductionSignature(form) {
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-induction-form')) {
        return;
    }

    const root = form.querySelector('[data-induction-signature]');
    const submitBtn = form.querySelector('[data-induction-submit]');
    const checkbox = form.querySelector('[data-induction-acknowledge]');

    checkbox?.addEventListener('change', () => updateInductionSubmitState(form));
    form.querySelectorAll('[data-induction-question]').forEach((input) => {
        input.addEventListener('input', () => updateInductionSubmitState(form));
    });

    form.addEventListener('submit', (e) => {
        syncInductionSignature(form);
        updateInductionSubmitState(form);
        if (submitBtn instanceof HTMLButtonElement && submitBtn.disabled) {
            e.preventDefault();
        }
    });

    if (!(root instanceof HTMLElement)) {
        updateInductionSubmitState(form);
        return;
    }

    const canvas = root.querySelector('[data-induction-signature-canvas]');
    const clearBtn = root.querySelector('[data-induction-signature-clear]');
    const hidden = root.querySelector('[data-induction-signature-output]');
    if (!(canvas instanceof HTMLCanvasElement) || !(hidden instanceof HTMLInputElement)) {
        updateInductionSubmitState(form);
        return;
    }

    const signaturePad = new SignaturePad(canvas, {
        penColor: '#1c1c1c',
        minWidth: 1.1,
        maxWidth: 2.2,
        backgroundColor: 'rgb(255,255,255)',
    });
    canvas._inductionSignaturePad = signaturePad;
    canvas.style.touchAction = 'none';
    canvas.style.cursor = 'crosshair';

    function resizeCanvas() {
        const wrap = canvas.parentElement;
        const cssW = Math.max(260, Math.floor(wrap?.clientWidth ?? 400));
        const cssH = 160;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.style.width = `${cssW}px`;
        canvas.style.height = `${cssH}px`;
        canvas.width = Math.floor(cssW * ratio);
        canvas.height = Math.floor(cssH * ratio);
        const ctx = canvas.getContext('2d');
        ctx?.setTransform(ratio, 0, 0, ratio, 0, 0);
        signaturePad.clear();
        updateInductionSubmitState(form);
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    signaturePad.addEventListener('endStroke', () => updateInductionSubmitState(form));

    clearBtn?.addEventListener('click', () => {
        signaturePad.clear();
        hidden.value = '';
        updateInductionSubmitState(form);
    });

    updateInductionSubmitState(form);
}
