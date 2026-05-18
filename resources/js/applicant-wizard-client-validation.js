import { validateApplicantGrantRange } from './applicant-wizard-grant-range.js';

/**
 * @typedef {{ field: string, value: string }} RequiredWhen
 * @typedef {{ field: string, days: number }} DateMinAfter
 * @typedef {{ name: string, type: string, label: string, required: boolean, maxLength?: number|null, maxWords?: number|null, minWords?: number|null, min?: number|null, max?: number|null, allowedValues?: string[], accept?: string, maxSizeMb?: number, requiredWhen?: RequiredWhen, dateMinAfter?: DateMinAfter, dateMinToday?: boolean }} FieldSpec
 * @typedef {{ step: number, grantRange: boolean, fields: FieldSpec[] }} ValidationPlan
 */

/**
 * @param {string} s
 */
function countWords(s) {
    const t = String(s ?? '').trim();
    if (t === '') {
        return 0;
    }
    return t.split(/\s+/u).length;
}

/**
 * @param {string} s
 */
function trim(s) {
    return String(s ?? '').trim();
}

/**
 * @param {number} n
 * @param {{ min?: number|null, max?: number|null }} bounds
 * @returns {string | null}
 */
function numericBoundsViolationMessage(n, bounds) {
    const lo = bounds.min;
    const hi = bounds.max;
    const hasMin = lo != null && Number.isFinite(Number(lo));
    const hasMax = hi != null && Number.isFinite(Number(hi));
    if (!hasMin && !hasMax) {
        return null;
    }
    if (hasMin && n < Number(lo)) {
        return `must be at least ${lo}.`;
    }
    if (hasMax && n > Number(hi)) {
        return `must be at most ${hi}.`;
    }
    return null;
}

/**
 * @param {string} s
 * @returns {number|null} UTC ms at midnight for Y-m-d (calendar compare, no local TZ shift)
 */
function parseYmdUtcMs(s) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(trim(s));
    if (!m) {
        return null;
    }
    const y = Number(m[1]);
    const mo = Number(m[2]);
    const d = Number(m[3]);
    if (!Number.isFinite(y) || !Number.isFinite(mo) || !Number.isFinite(d)) {
        return null;
    }
    const t = Date.UTC(y, mo - 1, d);
    return Number.isNaN(t) ? null : t;
}

/**
 * Local calendar date as Y-m-d (matches Flatpickr `minDate: 'today'` in the user's browser).
 */
function todayYmdLocal() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/**
 * Read a top-level wizard answer (answers[field]) without querySelector name="…[brackets]",
 * which is brittle. Prefer direct `name` comparison on `form.elements`.
 *
 * @param {HTMLFormElement} form
 * @param {string} fieldName
 */
function readApplicantAnswerValue(form, fieldName) {
    const fullName = `answers[${fieldName}]`;
    for (const el of form.elements) {
        if (el instanceof HTMLInputElement && el.type === 'radio' && el.name === fullName && el.checked) {
            return el.value;
        }
    }
    for (const el of form.elements) {
        if (el instanceof HTMLSelectElement && el.name === fullName) {
            return el.value;
        }
    }
    for (const el of form.elements) {
        if (el instanceof HTMLTextAreaElement && el.name === fullName) {
            return el.value;
        }
        if (el instanceof HTMLInputElement && el.name === fullName && el.type !== 'radio') {
            return el.value;
        }
    }
    return '';
}

/**
 * @param {HTMLFormElement} form
 * @param {FieldSpec} spec
 */
function fieldIsRequired(form, spec) {
    if (spec.required) {
        return true;
    }
    const rw = spec.requiredWhen;
    if (!rw?.field) {
        return false;
    }
    return readApplicantAnswerValue(form, rw.field) === String(rw.value ?? '');
}

/**
 * @param {HTMLFormElement} form
 */
export function syncApplicantWizardConditionalVisibility(form) {
    for (const block of form.querySelectorAll('[data-wff-visible-when-field]')) {
        if (!(block instanceof HTMLElement)) {
            continue;
        }
        const f = block.getAttribute('data-wff-visible-when-field') || '';
        const want = block.getAttribute('data-wff-visible-when-value') ?? '';
        const cur = readApplicantAnswerValue(form, f);
        const match = String(cur).trim() === String(want).trim();
        block.hidden = !match;
    }
}

/**
 * Show / hide blocks that depend on another field (e.g. bank details when "yes").
 * Deferred with rAF so label → radio updates complete before we read `.checked`.
 *
 * @param {HTMLFormElement} form
 */
export function initApplicantWizardConditionalVisibility(form) {
    if (form.dataset.wffVisibleWhenBound === '1') {
        return;
    }
    form.dataset.wffVisibleWhenBound = '1';
    const run = () => {
        window.requestAnimationFrame(() => {
            syncApplicantWizardConditionalVisibility(form);
        });
    };
    run();
    form.addEventListener('change', run);
    form.addEventListener('input', run);
}

/**
 * @param {HTMLFormElement} form
 * @param {FieldSpec} spec
 * @returns {{ message: string, element: HTMLElement, repeatableCompound?: boolean } | null}
 */
function validateField(form, spec) {
    const { name, type } = spec;
    const label = spec.label || name;
    const required = fieldIsRequired(form, spec);

    if (type === 'repeatable_text') {
        const inputs = form.querySelectorAll(
            `textarea[name="answers[${name}][]"], input[name="answers[${name}][]"]`,
        );
        const nonEmpty = [...inputs].filter((i) => trim(i.value) !== '');
        if (required && nonEmpty.length === 0) {
            const first = inputs[0];
            return {
                message: `${label}: add at least one entry.`,
                element: first ?? form,
                repeatableCompound: true,
            };
        }
        const maxLen = spec.maxLength ?? 0;
        const maxWords = spec.maxWords ?? 0;
        const minWords = spec.minWords ?? 0;
        for (const inp of inputs) {
            if (trim(inp.value) === '') {
                continue;
            }
            if (maxLen > 0 && inp.value.length > maxLen) {
                return { message: `${label}: each line must be at most ${maxLen} characters.`, element: inp };
            }
            const wc = countWords(inp.value);
            if (minWords > 0 && wc < minWords) {
                return { message: `${label}: each line must be at least ${minWords} words.`, element: inp };
            }
            if (maxWords > 0 && wc > maxWords) {
                return { message: `${label}: each line must be at most ${maxWords} words.`, element: inp };
            }
        }
        return null;
    }

    if (type === 'repeatable_group') {
        const minRows = Math.max(0, Number(spec.minRows ?? 0) || 0);
        const subfields = spec.subfields ?? [];
        const groupRoot = [...form.querySelectorAll('[data-applicant-repeatable-group]')].find(
            (r) => r.getAttribute('data-repeatable-field') === name,
        );
        if (!groupRoot) {
            return null;
        }
        const rows = groupRoot.querySelectorAll('[data-repeatable-group-row]');
        if (required && rows.length < minRows) {
            const el = rows[0]?.querySelector('[data-repeatable-subfield]') ?? groupRoot;
            return {
                message: `${label}: add at least ${minRows} row(s).`,
                element: el,
                repeatableCompound: true,
            };
        }
        const maxRows = spec.maxRows != null ? Math.max(1, Number(spec.maxRows) || 0) : null;
        if (maxRows != null && rows.length > maxRows) {
            const el = rows[maxRows]?.querySelector('[data-repeatable-subfield]') ?? groupRoot;
            return {
                message: `${label}: at most ${maxRows} row(s) allowed.`,
                element: el,
                repeatableCompound: true,
            };
        }
        let rowNum = 0;
        for (const row of rows) {
            rowNum += 1;
            const hasAny = subfields.some((sf) => {
                const wrap = row.querySelector(`[data-repeatable-subfield="${sf.name}"]`);
                if (!wrap) {
                    return false;
                }
                if (sf.type === 'checkbox') {
                    return wrap.querySelectorAll('input[type="checkbox"]:checked').length > 0;
                }
                if (
                    wrap instanceof HTMLInputElement ||
                    wrap instanceof HTMLTextAreaElement ||
                    wrap instanceof HTMLSelectElement
                ) {
                    return trim(wrap.value) !== '';
                }
                return false;
            });
            if (!required && !hasAny) {
                continue;
            }
            for (const sf of subfields) {
                const control = row.querySelector(`[data-repeatable-subfield="${sf.name}"]`);
                if (!control) {
                    continue;
                }

                if (sf.type === 'checkbox') {
                    const checked = [...control.querySelectorAll('input[type="checkbox"]:checked')];
                    if (sf.required && checked.length === 0) {
                        const firstCb = control.querySelector('input[type="checkbox"]');
                        return {
                            message: `${label}, row ${rowNum}: ${sf.label || sf.name} is required.`,
                            element: firstCb ?? control,
                            repeatableCompound: true,
                        };
                    }
                    for (const cb of checked) {
                        if (sf.allowedValues?.length && !sf.allowedValues.includes(cb.value)) {
                            return {
                                message: `${label}, row ${rowNum}: ${sf.label || sf.name} has an invalid value.`,
                                element: cb,
                                repeatableCompound: true,
                            };
                        }
                    }
                    continue;
                }

                if (
                    !(
                        control instanceof HTMLInputElement ||
                        control instanceof HTMLTextAreaElement ||
                        control instanceof HTMLSelectElement
                    )
                ) {
                    continue;
                }
                const tv = trim(control.value);
                if (sf.required && tv === '') {
                    return {
                        message: `${label}, row ${rowNum}: ${sf.label || sf.name} is required.`,
                        element: control,
                        repeatableCompound: true,
                    };
                }
                if (tv === '') {
                    continue;
                }
                if (sf.type === 'number') {
                    const parsed = Number.parseFloat(control.value);
                    if (Number.isNaN(parsed)) {
                        return {
                            message: `${label}, row ${rowNum}: ${sf.label || sf.name} must be a valid number.`,
                            element: control,
                            repeatableCompound: true,
                        };
                    }
                    const boundMsg = numericBoundsViolationMessage(parsed, sf);
                    if (boundMsg) {
                        return {
                            message: `${label}, row ${rowNum}: ${sf.label || sf.name} ${boundMsg}`,
                            element: control,
                            repeatableCompound: true,
                        };
                    }
                }
                if (sf.type === 'date') {
                    if (tv !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(tv)) {
                        return {
                            message: `${label}, row ${rowNum}: ${sf.label || sf.name} must be a valid date.`,
                            element: control,
                            repeatableCompound: true,
                        };
                    }
                    const dma = sf.dateMinAfter;
                    if (dma?.field && tv !== '') {
                        const startVal = trim(readApplicantAnswerValue(form, dma.field));
                        const startMs = parseYmdUtcMs(startVal);
                        const endMs = parseYmdUtcMs(tv);
                        const offsetDays = Math.max(0, Number(dma.days ?? 1) || 0);
                        if (startMs !== null && endMs !== null) {
                            const minEndMs = startMs + offsetDays * 86400000;
                            if (endMs < minEndMs) {
                                const subLab = sf.label || sf.name;
                                const msg =
                                    offsetDays === 1
                                        ? `${label}, row ${rowNum}: ${subLab} must be at least one calendar day after the related date.`
                                        : `${label}, row ${rowNum}: ${subLab} must be at least ${offsetDays} calendar days after the related date.`;
                                return {
                                    message: msg,
                                    element: control,
                                    repeatableCompound: true,
                                };
                            }
                        }
                    }
                    if (sf.dateMinToday && tv !== '') {
                        const today = todayYmdLocal();
                        if (tv < today) {
                            return {
                                message: `${label}, row ${rowNum}: ${sf.label || sf.name} cannot be before today.`,
                                element: control,
                                repeatableCompound: true,
                            };
                        }
                    }
                    continue;
                }
                if (sf.type === 'select' && sf.allowedValues?.length && !sf.allowedValues.includes(tv)) {
                    return {
                        message: `${label}, row ${rowNum}: ${sf.label || sf.name} has an invalid value.`,
                        element: control,
                        repeatableCompound: true,
                    };
                }
                const maxLen = sf.maxLength ?? 0;
                if (maxLen > 0 && control.value.length > maxLen) {
                    return {
                        message: `${label}, row ${rowNum}: ${sf.label || sf.name} must be at most ${maxLen} characters.`,
                        element: control,
                        repeatableCompound: true,
                    };
                }
                const maxWords = sf.maxWords ?? 0;
                const minWords = sf.minWords ?? 0;
                const wc = countWords(control.value);
                if (minWords > 0 && wc < minWords) {
                    return {
                        message: `${label}, row ${rowNum}: ${sf.label || sf.name} must be at least ${minWords} words.`,
                        element: control,
                        repeatableCompound: true,
                    };
                }
                if (maxWords > 0 && wc > maxWords) {
                    return {
                        message: `${label}, row ${rowNum}: ${sf.label || sf.name} must be at most ${maxWords} words.`,
                        element: control,
                        repeatableCompound: true,
                    };
                }
            }
        }
        return null;
    }

    if (type === 'radio') {
        const labelId = `answers_${name}_label`;
        const group = form.querySelector(`[role="radiogroup"][aria-labelledby="${labelId}"]`);
        const checked = form.querySelector(`input[name="answers[${name}]"]:checked`);
        if (required && !checked) {
            const first = form.querySelector(`input[name="answers[${name}]"]`);
            return { message: `${label} is required.`, element: group ?? first ?? form };
        }
        if (checked && spec.allowedValues?.length && !spec.allowedValues.includes(checked.value)) {
            return { message: `${label} has an invalid value.`, element: group ?? checked };
        }
        return null;
    }

    if (type === 'select') {
        const sel = form.querySelector(`select[name="answers[${name}]"]`);
        if (!sel) {
            return null;
        }
        const val = sel.value;
        if (required && val === '') {
            return { message: `${label} is required.`, element: sel };
        }
        if (val !== '' && spec.allowedValues?.length && !spec.allowedValues.includes(val)) {
            return { message: `${label} has an invalid value.`, element: sel };
        }
        return null;
    }

    if (type === 'checkbox') {
        const checked = [...form.querySelectorAll(`input[type="checkbox"][name="answers[${name}][]"]:checked`)];
        if (required && checked.length === 0) {
            const first = form.querySelector(`input[type="checkbox"][name="answers[${name}][]"]`);
            return { message: `${label} is required.`, element: first ?? form };
        }
        const allowed = spec.allowedValues ?? [];
        for (const c of checked) {
            if (allowed.length && !allowed.includes(c.value)) {
                return { message: `${label} has an invalid value.`, element: c };
            }
        }
        return null;
    }

    if (type === 'file') {
        const el = form.querySelector(`input[type="file"][name="answers[${name}]"]`);
        if (!el) {
            return null;
        }
        const root = el.closest('[data-applicant-file-field]');
        const hasExisting = root?.hasAttribute('data-applicant-file-has-existing');
        const maxMb = Math.max(1, Number(spec.maxSizeMb ?? 10) || 10);

        if (name === 'head_organisation_signature_image') {
            const methodRadio = form.querySelector(
                'input[name="answers[head_organisation_signature_method]"]:checked',
            );
            const method = methodRadio instanceof HTMLInputElement ? methodRadio.value : '';
            const headRoot = form.querySelector('[data-applicant-head-signature]');
            const hidden = headRoot?.querySelector('[data-applicant-signature-canvas-png]');
            const hasDrawPayload =
                hidden instanceof HTMLInputElement &&
                hidden.value.trimStart().startsWith('data:image/png;base64,');

            if (method === 'draw') {
                if (!required || hasExisting || hasDrawPayload) {
                    const f = el.files?.[0];
                    if (f && f.size > maxMb * 1024 * 1024) {
                        return { message: `${label} must be at most ${maxMb} MB.`, element: el };
                    }
                    return null;
                }
                const canvas = headRoot?.querySelector('[data-applicant-signature-canvas]');
                return {
                    message:
                        'Draw your signature in the box, or switch to “Upload an image of my signature” and choose a file.',
                    element: canvas instanceof HTMLElement ? canvas : el,
                };
            }

            if (
                required &&
                (!el.files || el.files.length === 0) &&
                !hasExisting
            ) {
                return {
                    message:
                        'Upload a signature image, or switch to “Draw my signature here” and sign in the box.',
                    element: el,
                };
            }
            const f = el.files?.[0];
            if (f && f.size > maxMb * 1024 * 1024) {
                return { message: `${label} must be at most ${maxMb} MB.`, element: el };
            }
            return null;
        }

        if (required && (!el.files || el.files.length === 0) && !hasExisting) {
            return { message: `${label} is required.`, element: el };
        }
        const f = el.files?.[0];
        if (f && f.size > maxMb * 1024 * 1024) {
            return { message: `${label} must be at most ${maxMb} MB.`, element: el };
        }
        return null;
    }

    const byName = form.querySelector(`input[name="answers[${name}]"], textarea[name="answers[${name}]"]`);

    if (type === 'date') {
        const el = byName;
        if (!el) {
            return null;
        }
        const val = trim(el.value);
        if (required && val === '') {
            return { message: `${label} is required.`, element: el };
        }
        if (val !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(val)) {
            return { message: `${label} must be a valid date.`, element: el };
        }
        const dma = spec.dateMinAfter;
        if (dma?.field && val !== '') {
            const startVal = trim(readApplicantAnswerValue(form, dma.field));
            const startMs = parseYmdUtcMs(startVal);
            const endMs = parseYmdUtcMs(val);
            const offsetDays = Math.max(0, Number(dma.days ?? 1) || 0);
            if (startMs !== null && endMs !== null) {
                const minEndMs = startMs + offsetDays * 86400000;
                if (endMs < minEndMs) {
                    const msg =
                        offsetDays === 1
                            ? `${label} must be at least one day after the project start date.`
                            : `${label} must be at least ${offsetDays} calendar days after the project start date.`;
                    return { message: msg, element: el };
                }
            }
        }
        if (spec.dateMinToday && val !== '') {
            const today = todayYmdLocal();
            if (val < today) {
                return { message: `${label} cannot be before today.`, element: el };
            }
        }
        return null;
    }

    if (type === 'number') {
        const el = byName;
        if (!el) {
            return null;
        }
        const raw = trim(el.value);
        if (required && raw === '') {
            return { message: `${label} is required.`, element: el };
        }
        if (raw !== '') {
            const parsed = Number.parseFloat(raw);
            if (Number.isNaN(parsed)) {
                return { message: `${label} must be a valid number.`, element: el };
            }
            const boundMsg = numericBoundsViolationMessage(parsed, spec);
            if (boundMsg) {
                return { message: `${label} ${boundMsg}`, element: el };
            }
        }
        return null;
    }

    const el = form.querySelector(`textarea[name="answers[${name}]"], input[name="answers[${name}]"]`);
    if (!el) {
        return null;
    }
    const val = el.value;
    const t = trim(val);
    if (required && t === '') {
        return { message: `${label} is required.`, element: el };
    }
    if (t === '') {
        return null;
    }
    const maxWords = spec.maxWords ?? 0;
    const minWords = spec.minWords ?? 0;
    const wc = countWords(val);
    if (minWords > 0 && wc < minWords) {
        return { message: `${label} must be at least ${minWords} words.`, element: el };
    }
    if (maxWords > 0 && wc > maxWords) {
        return { message: `${label} must be at most ${maxWords} words.`, element: el };
    }
    const maxLen = spec.maxLength ?? 0;
    if (maxLen > 0 && val.length > maxLen) {
        return { message: `${label} must be at most ${maxLen} characters.`, element: el };
    }
    return null;
}

/**
 * @param {HTMLElement} el
 */
function stripClientValidationHighlight(el) {
    el.classList.remove(
        'border-destructive',
        'ring-2',
        'ring-destructive',
        'rounded-lg',
        'ring-offset-2',
        'ring-offset-background',
    );
}

/**
 * @param {HTMLElement} markedEl
 */
function clientValidationMessageHost(markedEl) {
    if (markedEl.getAttribute('role') === 'radiogroup') {
        return markedEl;
    }
    if (markedEl.matches('input[type="file"]')) {
        return markedEl.closest('[data-applicant-file-field]') ?? markedEl.parentElement;
    }
    if (markedEl.matches('input, textarea, select')) {
        return markedEl.closest('[data-char-count-root]') ?? markedEl.parentElement;
    }
    return markedEl.parentElement;
}

/**
 * @param {HTMLElement} markedEl
 */
function clearClientValidationMarkedElement(markedEl) {
    if (!markedEl?.hasAttribute('data-client-validation-mark')) {
        return;
    }
    stripClientValidationHighlight(markedEl);
    markedEl.removeAttribute('data-client-validation-mark');
    const host = clientValidationMessageHost(markedEl);
    host?.querySelectorAll(':scope > [data-client-validation-msg]').forEach((n) => n.remove());
    markedEl.closest('[data-applicant-repeatable-text]')?.removeAttribute('data-client-validation-compound-error');
    markedEl.closest('[data-applicant-repeatable-group]')?.removeAttribute('data-client-validation-compound-error');
}

/**
 * @param {HTMLElement} repeatableRoot
 */
function clearRepeatableClientValidation(repeatableRoot) {
    repeatableRoot.querySelectorAll('[data-client-validation-mark]').forEach((el) => {
        stripClientValidationHighlight(el);
        el.removeAttribute('data-client-validation-mark');
    });
    repeatableRoot.querySelectorAll('[data-client-validation-msg]').forEach((n) => n.remove());
    repeatableRoot.removeAttribute('data-client-validation-compound-error');
}

/**
 * @param {HTMLElement} repeatableRoot
 */
function clearRepeatableGroupClientValidation(repeatableRoot) {
    repeatableRoot.querySelectorAll('[data-client-validation-mark]').forEach((el) => {
        stripClientValidationHighlight(el);
        el.removeAttribute('data-client-validation-mark');
    });
    repeatableRoot.querySelectorAll('[data-client-validation-msg]').forEach((n) => n.remove());
    repeatableRoot.removeAttribute('data-client-validation-compound-error');
}

/**
 * @param {HTMLFormElement} form
 */
function rebuildClientValidationSummary(form) {
    const summary = document.getElementById('applicant-client-validation-summary');
    if (!summary || summary.classList.contains('hidden')) {
        return;
    }
    const msgs = [...form.querySelectorAll('[data-client-validation-msg]')]
        .map((p) => (p.textContent ?? '').trim())
        .filter(Boolean);
    if (msgs.length === 0) {
        summary.classList.add('hidden');
        summary.textContent = '';
        summary.innerHTML = '';
        return;
    }
    summary.innerHTML = '<p class="font-medium">Please fix the following before continuing:</p>';
    const ul = document.createElement('ul');
    ul.className = 'mt-2 list-inside list-disc space-y-1';
    for (const m of msgs) {
        const li = document.createElement('li');
        li.textContent = m;
        ul.appendChild(li);
    }
    summary.appendChild(ul);
}

/**
 * @param {HTMLFormElement} form
 * @param {HTMLElement} target
 */
function clearClientValidationForEngagement(form, target) {
    if (!(target instanceof HTMLElement) || !form.contains(target)) {
        return;
    }

    const repRoot = target.closest('[data-applicant-repeatable-text]');
    if (
        repRoot &&
        repRoot.hasAttribute('data-client-validation-compound-error') &&
        target.matches('input[name*="[]"], textarea[name*="[]"]')
    ) {
        clearRepeatableClientValidation(repRoot);
        rebuildClientValidationSummary(form);
        return;
    }

    const grpRoot = target.closest('[data-applicant-repeatable-group]');
    if (
        grpRoot &&
        grpRoot.hasAttribute('data-client-validation-compound-error') &&
        target.matches('input[name*="["], textarea[name*="["], select[name*="["]')
    ) {
        clearRepeatableGroupClientValidation(grpRoot);
        rebuildClientValidationSummary(form);
        return;
    }

    const marked = target.closest('[data-client-validation-mark]');
    if (marked) {
        clearClientValidationMarkedElement(marked);
        rebuildClientValidationSummary(form);
    }
}

/**
 * Clears server-rendered @error styling/messages for the engaged control (vanilla DOM, no jQuery).
 *
 * @param {HTMLElement} control
 * @param {HTMLFormElement} form
 */
function clearServerFieldErrorUi(control, form) {
    if (!control.matches('input, textarea, select')) {
        return;
    }
    if (control.type === 'hidden' || control.disabled) {
        return;
    }

    control.classList.remove('border-destructive');
    control.removeAttribute('aria-invalid');

    const name = control.getAttribute('name');
    if (control.type === 'radio' && name && typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        form.querySelectorAll(`input[type="radio"][name="${CSS.escape(name)}"]`).forEach((r) => {
            r.classList.remove('border-destructive');
            r.removeAttribute('aria-invalid');
        });
    }

    let el = control;
    while (el && el !== form) {
        el.querySelectorAll(':scope > p[role="alert"].text-destructive:not([data-client-validation-msg])').forEach((p) => {
            p.remove();
        });
        el = el.parentElement;
    }
}

/**
 * @param {HTMLElement} eventTarget
 * @returns {HTMLElement | null}
 */
function resolveEngagedControl(eventTarget) {
    if (!(eventTarget instanceof Element)) {
        return null;
    }
    let control = eventTarget.closest('input, textarea, select');
    if (!control) {
        const lb = eventTarget.closest('label');
        control = lb?.querySelector('input, textarea, select') ?? null;
    }
    return control instanceof HTMLElement ? control : null;
}

/**
 * Ignore the next focus/click-driven dismiss pass (e.g. after programmatic `.focus()` on first invalid field).
 *
 * @param {HTMLFormElement} form
 */
function suppressValidationDismissUntilAfterTask(form) {
    form.dataset.applicantWizardSuppressValidationDismiss = '1';
    const lift = () => {
        delete form.dataset.applicantWizardSuppressValidationDismiss;
    };
    if (typeof queueMicrotask === 'function') {
        queueMicrotask(lift);
    } else {
        setTimeout(lift, 0);
    }
}

/**
 * @param {HTMLFormElement} form
 */
function bindApplicantWizardValidationDismissOnEngage(form) {
    if (form.dataset.applicantWizardValidationDismissBound === '1') {
        return;
    }
    form.dataset.applicantWizardValidationDismissBound = '1';

    const onEngage = (e) => {
        if (form.dataset.applicantWizardSuppressValidationDismiss === '1') {
            return;
        }
        const control = resolveEngagedControl(e.target instanceof Element ? e.target : null);
        if (!control || !form.contains(control)) {
            return;
        }
        if (control.type === 'hidden' || control.disabled) {
            return;
        }
        clearClientValidationForEngagement(form, control);
        clearServerFieldErrorUi(control, form);
    };

    form.addEventListener('focusin', onEngage);
    form.addEventListener('click', onEngage);
}

function clearClientValidationUi(form) {
    form.querySelectorAll('[data-client-validation-mark]').forEach((el) => {
        stripClientValidationHighlight(el);
        el.removeAttribute('data-client-validation-mark');
    });
    form.querySelectorAll('[data-client-validation-msg]').forEach((el) => el.remove());
    form.querySelectorAll(
        '[data-applicant-repeatable-text][data-client-validation-compound-error], [data-applicant-repeatable-group][data-client-validation-compound-error]',
    ).forEach((root) => {
        root.removeAttribute('data-client-validation-compound-error');
    });
    const summary = document.getElementById('applicant-client-validation-summary');
    if (summary) {
        summary.classList.add('hidden');
        summary.textContent = '';
        summary.innerHTML = '';
    }
}

/**
 * @param {HTMLFormElement} form
 * @param {Array<{ message: string, element: HTMLElement, repeatableCompound?: boolean }>} errors
 */
function showClientValidationSummary(errors) {
    const summary = document.getElementById('applicant-client-validation-summary');
    if (!summary) {
        return;
    }
    summary.classList.remove('hidden');
    const ul = document.createElement('ul');
    ul.className = 'mt-2 list-inside list-disc space-y-1';
    errors.forEach((err) => {
        const li = document.createElement('li');
        li.textContent = err.message;
        ul.appendChild(li);
    });
    summary.innerHTML = '<p class="font-medium">Please fix the following before continuing:</p>';
    summary.appendChild(ul);
}

/**
 * @param {HTMLElement} el
 * @param {string} message
 * @param {boolean} [repeatableCompound]
 */
function markFieldError(el, message, repeatableCompound = false) {
    el.setAttribute('data-client-validation-mark', '1');
    if (el.getAttribute('role') === 'radiogroup') {
        el.classList.add('ring-2', 'ring-destructive', 'rounded-lg', 'ring-offset-2', 'ring-offset-background');
    } else if (el.matches('input, textarea, select')) {
        el.classList.add('border-destructive');
    }

    let rep = null;
    if (repeatableCompound) {
        if (el.matches('input[name*="[]"], textarea[name*="[]"]')) {
            rep = el.closest('[data-applicant-repeatable-text]');
        }
        if (!rep && el.matches('input[name*="["], textarea[name*="["], select[name*="["]')) {
            rep = el.closest('[data-applicant-repeatable-group]');
        }
    }
    rep?.setAttribute('data-client-validation-compound-error', '1');

    const host = clientValidationMessageHost(el);

    if (!host) {
        return;
    }
    host.querySelectorAll(':scope > [data-client-validation-msg]').forEach((n) => n.remove());
    const p = document.createElement('p');
    p.className = 'mt-1 text-sm text-destructive';
    p.setAttribute('data-client-validation-msg', '1');
    p.setAttribute('role', 'alert');
    p.textContent = message;
    host.appendChild(p);
}

/**
 * @param {HTMLFormElement} form
 */
function readValidationPlan(form) {
    const raw = document.getElementById('applicant-wizard-client-validation-plan')?.textContent?.trim();
    if (!raw) {
        return null;
    }
    try {
        return /** @type {ValidationPlan} */ (JSON.parse(raw));
    } catch {
        return null;
    }
}

/**
 * @param {HTMLFormElement} form
 */
export function initApplicantWizardClientValidation(form) {
    bindApplicantWizardValidationDismissOnEngage(form);

    const plan = readValidationPlan(form);
    if (!plan?.fields?.length) {
        return;
    }

    form.addEventListener(
        'submit',
        (e) => {
            const intentEl = form.querySelector('#wff-applicant-wizard-intent');
            if (
                intentEl instanceof HTMLInputElement &&
                !intentEl.disabled &&
                intentEl.value === 'complete_later'
            ) {
                return;
            }

            clearClientValidationUi(form);
            syncApplicantWizardConditionalVisibility(form);

            if (plan.grantRange && form.hasAttribute('data-applicant-grant-range')) {
                if (!validateApplicantGrantRange(form)) {
                    e.preventDefault();
                    const totalInput = form.querySelector('#answers_total_grant_request_fjd');
                    if (totalInput && typeof totalInput.reportValidity === 'function') {
                        totalInput.reportValidity();
                    }
                    suppressValidationDismissUntilAfterTask(form);
                    totalInput?.focus();
                    return;
                }
            }

            /** @type {Array<{ message: string, element: HTMLElement, repeatableCompound?: boolean }>} */
            const errors = [];
            for (const spec of plan.fields) {
                const err = validateField(form, spec);
                if (err) {
                    errors.push(err);
                }
            }

            if (errors.length === 0) {
                return;
            }

            e.preventDefault();
            showClientValidationSummary(errors);
            errors.forEach((err) => markFieldError(err.element, err.message, err.repeatableCompound));
            const first = errors[0].element;
            const focusTarget =
                first.getAttribute('role') === 'radiogroup'
                    ? first.querySelector('input[type="radio"]')
                    : first;
            focusTarget?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (focusTarget && typeof focusTarget.focus === 'function') {
                suppressValidationDismissUntilAfterTask(form);
                focusTarget.focus();
            }
        },
        { capture: true },
    );
}
