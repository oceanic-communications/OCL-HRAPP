/**
 * Full-page-card overlay with WFF logo while a form POST is in flight.
 * Pair `form[data-wff-form-loading]` with a root `[data-wff-form-loading-root]` and overlay `[data-wff-form-loading-overlay]`.
 * Optional per-button copy: `button[data-wff-loading-message="…"]`.
 * Deferred with setTimeout(0): queueMicrotask runs before the browser serializes the form for navigation,
 * so disabling submit buttons there can drop the submitter's name/value (e.g. `form_action`) from the POST.
 */
export function initWffFormLoading() {
    document.querySelectorAll('form[data-wff-form-loading]').forEach((form) => {
        let root = form.closest('[data-wff-form-loading-root]');
        let overlay = root ? root.querySelector('[data-wff-form-loading-overlay]') : null;
        if (!overlay) {
            overlay = form.querySelector('[data-wff-form-loading-overlay]');
            if (overlay) {
                root = form;
            }
        }
        if (!root || !overlay) {
            return;
        }
        const messageEl = overlay.querySelector('[data-wff-form-loading-message]');
        const defaultMessage = overlay.getAttribute('data-default-message') || 'Please wait…';

        form.addEventListener('submit', (e) => {
            const submitter = e.submitter;
            const reveal = () => {
                if (e.defaultPrevented) {
                    return;
                }
                const custom =
                    (typeof submitter?.getAttribute === 'function' &&
                        submitter.getAttribute('data-wff-loading-message')?.trim()) ||
                    '';
                if (messageEl) {
                    messageEl.textContent = custom || defaultMessage;
                }
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                overlay.setAttribute('aria-hidden', 'false');
                form.setAttribute('aria-busy', 'true');
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
                    if (submitter && btn === submitter) {
                        return;
                    }
                    btn.disabled = true;
                });
            };
            setTimeout(reveal, 0);
        });
    });
}
