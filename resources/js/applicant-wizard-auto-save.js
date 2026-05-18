/**
 * Debounced background save for the applicant grant wizard (draft validation, no step advance).
 * Skips when the user has selected new files (use Save buttons to upload).
 */

import { syncApplicantHeadSignatureTransportFields } from './applicant-head-signature';

const DEBOUNCE_MS = 3000;
const SAVED_MSG_CLEAR_MS = 4000;

/**
 * @param {HTMLFormElement} form
 */
function formHasNewFileSelections(form) {
    return Array.from(form.querySelectorAll('input[type="file"]')).some(
        (el) => el instanceof HTMLInputElement && el.files && el.files.length > 0,
    );
}

/**
 * @param {HTMLFormElement} form
 */
export function initApplicantWizardAutoSave(form) {
    const statusEl = document.getElementById('wff-applicant-autosave-status');
    if (!statusEl || !(form instanceof HTMLFormElement)) {
        return;
    }

    let debounceTimer = 0;
    /** @type {AbortController | null} */
    let abortCtl = null;
    /** @type {ReturnType<typeof setTimeout> | null} */
    let clearStatusTimer = null;

    function setStatus(text) {
        statusEl.textContent = text;
    }

    function clearSavedMessageLater() {
        if (clearStatusTimer !== null) {
            clearTimeout(clearStatusTimer);
        }
        clearStatusTimer = setTimeout(() => {
            clearStatusTimer = null;
            if (statusEl.textContent === 'Draft saved') {
                setStatus('');
            }
        }, SAVED_MSG_CLEAR_MS);
    }

    async function performSave() {
        if (formHasNewFileSelections(form)) {
            return;
        }
        syncApplicantHeadSignatureTransportFields(form);
        abortCtl?.abort();
        abortCtl = new AbortController();
        const { signal } = abortCtl;

        /*
         * Put wizard_intent first: huge answers[head_organisation_signature_canvas_png] can make the
         * multipart body large; if PHP/nginx truncates the tail, trailing fields were lost and Laravel
         * saw no auto_save → non-draft rules → "Signature image is required".
         */
        const fromForm = new FormData(form);
        const fd = new FormData();
        fd.append('wizard_intent', 'auto_save');
        for (const [key, value] of fromForm.entries()) {
            if (key === 'wizard_intent') {
                continue;
            }
            fd.append(key, value);
        }

        const action = form.getAttribute('action');
        if (!action) {
            return;
        }

        setStatus('Saving…');

        try {
            const res = await fetch(action, {
                method: 'POST',
                body: fd,
                signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                let detail = '';
                try {
                    const data = await res.json();
                    if (data && typeof data.message === 'string' && data.message.trim() !== '') {
                        detail = data.message;
                    } else if (data && data.errors && typeof data.errors === 'object') {
                        const first = Object.values(data.errors).find((v) => Array.isArray(v) && v.length > 0);
                        if (first && typeof first[0] === 'string') {
                            detail = first[0];
                        }
                    }
                } catch {
                    /* ignore */
                }
                setStatus(detail || 'Could not auto-save. Use the Save buttons below.');
                return;
            }

            setStatus('Draft saved');
            clearSavedMessageLater();
        } catch (e) {
            if (e instanceof DOMException && e.name === 'AbortError') {
                return;
            }
            setStatus('Could not auto-save. Use the Save buttons below.');
        }
    }

    function scheduleSave() {
        if (formHasNewFileSelections(form)) {
            setStatus('Add or change files with the Save buttons below.');
            return;
        }
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            debounceTimer = 0;
            void performSave();
        }, DEBOUNCE_MS);
    }

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

    form.addEventListener('submit', () => {
        window.clearTimeout(debounceTimer);
        abortCtl?.abort();
    });

    window.setTimeout(() => {
        void performSave();
    }, 800);
}
