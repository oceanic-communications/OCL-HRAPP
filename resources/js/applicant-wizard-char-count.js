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
 * Live character or word counts for inputs wrapped in [data-char-count-root] (applicant grant wizard).
 *
 * @param {HTMLFormElement} form
 */
export function initApplicantWizardCharCount(form) {
    /**
     * Word bounds may live on the input/textarea or on [data-char-count-root] (fallback after
     * portal-auto-grow upgrades input→textarea, or when the wrapper carries limits).
     *
     * @param {HTMLElement} root
     * @param {HTMLElement} input
     */
    function wordBoundAttrs(root, input) {
        const maxA = input.getAttribute('data-max-words') || root.getAttribute('data-max-words');
        const minA = input.getAttribute('data-min-words') || root.getAttribute('data-min-words');
        return { maxA, minA };
    }

    function refresh(root) {
        if (!(root instanceof HTMLElement)) {
            return;
        }
        const input = root.querySelector('[data-char-count-input]');
        const display = root.querySelector('[data-char-count-display]');
        if (!input || !display) {
            return;
        }
        const { maxA: maxWordsAttr, minA: minWordsAttr } = wordBoundAttrs(root, input);
        const maxWords =
            maxWordsAttr !== null && maxWordsAttr !== '' ? Number.parseInt(maxWordsAttr, 10) : 0;
        const minWords =
            minWordsAttr !== null && minWordsAttr !== '' ? Number.parseInt(minWordsAttr, 10) : 0;
        const hasMax = Number.isFinite(maxWords) && maxWords > 0;
        const hasMin = Number.isFinite(minWords) && minWords > 0;
        if (hasMax || hasMin) {
            const wc = countWords(input.value);
            if (hasMax && hasMin) {
                display.textContent = `${wc} words (min ${minWords}, max ${maxWords})`;
            } else if (hasMax) {
                display.textContent = `${wc} / ${maxWords} words`;
            } else {
                display.textContent = `${wc} words (min ${minWords})`;
            }
            const tooLow = hasMin && wc < minWords;
            const tooHigh = hasMax && wc > maxWords;
            if (tooLow || tooHigh) {
                display.classList.add('text-destructive');
                display.classList.remove('text-muted-foreground');
            } else {
                display.classList.remove('text-destructive');
                display.classList.add('text-muted-foreground');
            }
            return;
        }
        const maxAttr = input.getAttribute('maxlength');
        const max = maxAttr !== null && maxAttr !== '' ? Number.parseInt(maxAttr, 10) : 0;
        const len = input.value.length;
        display.textContent = Number.isFinite(max) && max > 0 ? `${len} / ${max}` : String(len);
        if (Number.isFinite(max) && max > 0 && len > max) {
            display.classList.add('text-destructive');
            display.classList.remove('text-muted-foreground');
        } else {
            display.classList.remove('text-destructive');
            display.classList.add('text-muted-foreground');
        }
    }

    form.querySelectorAll('[data-char-count-root]').forEach((root) => refresh(root));

    form.addEventListener('input', (e) => {
        const el = e.target;
        if (!(el instanceof HTMLInputElement) && !(el instanceof HTMLTextAreaElement)) {
            return;
        }
        if (!el.matches('[data-char-count-input]')) {
            return;
        }
        const root = el.closest('[data-char-count-root]');
        if (root && form.contains(root)) {
            refresh(root);
        }
    });
}
