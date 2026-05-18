const RANGES = {
    established: { min: 20_000, max: 100_000 },
    emerging: { min: 5_000, max: 50_000 },
};

function formatFjd(n) {
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n);
}

/**
 * Step 1: validate total grant request vs organisation type (same rules as server-side intent).
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
export function validateApplicantGrantRange(form) {
    const typeInputs = form.querySelectorAll('input[name="answers[organisation_type]"][type="radio"]');
    const totalInput = form.querySelector('#answers_total_grant_request_fjd');
    if (!totalInput || typeInputs.length === 0) {
        return true;
    }

    let msgEl = document.getElementById('applicant-total-grant-range-msg');
    if (!msgEl) {
        msgEl = document.createElement('p');
        msgEl.id = 'applicant-total-grant-range-msg';
        msgEl.className = 'mt-1 text-sm text-muted-foreground';
        msgEl.setAttribute('aria-live', 'polite');
        totalInput.insertAdjacentElement('afterend', msgEl);
    }

    function selectedOrgType() {
        const el = form.querySelector('input[name="answers[organisation_type]"][type="radio"]:checked');
        return el?.value ?? '';
    }

    function rangeFor(type) {
        return type && Object.hasOwn(RANGES, type) ? RANGES[type] : null;
    }

    function setMessage(text, isError) {
        if (!text) {
            msgEl.textContent = '';
            msgEl.classList.add('hidden');
            msgEl.classList.remove('text-destructive', 'text-muted-foreground');
            return;
        }
        msgEl.textContent = text;
        msgEl.classList.remove('hidden');
        msgEl.classList.toggle('text-destructive', Boolean(isError));
        msgEl.classList.toggle('text-muted-foreground', !isError);
    }

    function applyMinMax() {
        const type = selectedOrgType();
        const range = rangeFor(type);
        if (!range) {
            totalInput.removeAttribute('min');
            totalInput.removeAttribute('max');
            return;
        }
        totalInput.min = String(range.min);
        totalInput.max = String(range.max);
    }

    applyMinMax();
    totalInput.setCustomValidity('');
    const type = selectedOrgType();
    const range = rangeFor(type);
    const raw = totalInput.value.trim();

    if (!range) {
        setMessage('', false);
        totalInput.classList.remove('border-destructive');
        return true;
    }

    if (raw === '') {
        setMessage('', false);
        totalInput.classList.remove('border-destructive');
        return true;
    }

    const n = Number.parseFloat(raw);
    if (Number.isNaN(n)) {
        const text = 'Enter a valid number for the total grant request.';
        totalInput.setCustomValidity(text);
        setMessage(text, true);
        totalInput.classList.add('border-destructive');
        return false;
    }

    if (n < range.min || n > range.max) {
        const text = `For your organisation type, the total grant request must be between FJD ${formatFjd(range.min)} and FJD ${formatFjd(range.max)}.`;
        totalInput.setCustomValidity(text);
        setMessage(text, true);
        totalInput.classList.add('border-destructive');
        return false;
    }

    setMessage(`Allowed range for your organisation type: FJD ${formatFjd(range.min)} – ${formatFjd(range.max)}.`, false);
    totalInput.classList.remove('border-destructive');
    return true;
}

/**
 * Client-side min/max for total grant request vs organisation type (step 1).
 *
 * @param {HTMLFormElement} form
 */
export function initApplicantWizardGrantRange(form) {
    const typeInputs = form.querySelectorAll('input[name="answers[organisation_type]"][type="radio"]');
    const totalInput = form.querySelector('#answers_total_grant_request_fjd');
    if (!totalInput || typeInputs.length === 0) {
        return;
    }

    typeInputs.forEach((input) => {
        input.addEventListener('change', () => {
            validateApplicantGrantRange(form);
        });
    });

    totalInput.addEventListener('input', () => {
        validateApplicantGrantRange(form);
    });

    totalInput.addEventListener('blur', () => {
        validateApplicantGrantRange(form);
    });

    validateApplicantGrantRange(form);
}
