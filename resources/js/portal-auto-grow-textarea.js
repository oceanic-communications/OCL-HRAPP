const TEXTAREA_SELECTOR = 'textarea[data-portal-auto-grow]';
const INPUT_SELECTOR = 'input[type="text"][data-portal-auto-grow]';

/**
 * Replace a single-line text input with a textarea so wrapped text can grow in height
 * (native <input type="text"> never wraps).
 *
 * @param {HTMLInputElement} input
 * @returns {HTMLTextAreaElement}
 */
function upgradePortalAutoGrowTextInput(input) {
    const ta = document.createElement('textarea');
    for (const { name, value } of [...input.attributes]) {
        if (name === 'type') {
            continue;
        }
        ta.setAttribute(name, value);
    }
    ta.rows = 1;
    ta.removeAttribute('value');
    ta.value = input.value;
    input.replaceWith(ta);
    return ta;
}

/**
 * Single-line-style textareas: grow height as content wraps (native <input type="text"> cannot).
 *
 * @param {HTMLTextAreaElement} el
 */
export function syncPortalAutoGrowTextarea(el) {
    if (!(el instanceof HTMLTextAreaElement)) {
        return;
    }
    el.style.overflow = 'hidden';
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
}

/**
 * @param {ParentNode} [root] document, form, fragment, or row container
 */
export function initPortalAutoGrowTextareas(root = document) {
    const scope = root && 'querySelectorAll' in root ? root : document;
    scope.querySelectorAll(INPUT_SELECTOR).forEach((el) => {
        if (!(el instanceof HTMLInputElement) || el.dataset.portalAutoGrowBound === '1') {
            return;
        }
        upgradePortalAutoGrowTextInput(el);
    });
    scope.querySelectorAll(TEXTAREA_SELECTOR).forEach((el) => {
        if (!(el instanceof HTMLTextAreaElement) || el.dataset.portalAutoGrowBound === '1') {
            return;
        }
        el.dataset.portalAutoGrowBound = '1';
        const run = () => syncPortalAutoGrowTextarea(el);
        el.addEventListener('input', run);
        queueMicrotask(run);
    });
}
