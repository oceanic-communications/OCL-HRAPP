/**
 * Help page walkthrough: one transient-style tooltip step at a time (app-like).
 */

const focusableSelector =
    'button:not([disabled]), [href], input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])';

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function parsePanels(root) {
    const el = root.querySelector('[data-help-walkthrough-panels]');
    if (!el?.textContent) {
        return [];
    }
    try {
        const data = JSON.parse(el.textContent);
        return Array.isArray(data) ? data : [];
    } catch {
        return [];
    }
}

/**
 * @param {HTMLElement} root
 */
export function initHelpWalkthrough(root) {
    const panels = parsePanels(root);
    if (panels.length === 0) {
        return;
    }

    const reduceMotion = prefersReducedMotion();
    const tooltip = root.querySelector('[data-help-walkthrough-tooltip]');
    const live = root.querySelector('[data-help-walkthrough-live]');
    const titleEl = root.querySelector('[data-help-walkthrough-title]');
    const bodyEl = root.querySelector('[data-help-walkthrough-body]');
    const ctaSlot = root.querySelector('[data-help-walkthrough-cta]');
    const stepLabel = root.querySelector('[data-help-walkthrough-step-label]');
    const dots = root.querySelector('[data-help-walkthrough-dots]');
    const prevBtn = root.querySelector('[data-help-walkthrough-prev]');
    const nextBtn = root.querySelector('[data-help-walkthrough-next]');
    const skipBtn = root.querySelector('[data-help-walkthrough-skip]');
    const replayRow = root.querySelector('[data-help-walkthrough-replay]');
    const replayBtn = root.querySelector('[data-help-walkthrough-replay-btn]');
    const mainTour = root.querySelector('[data-help-walkthrough-main]');

    if (
        !tooltip ||
        !titleEl ||
        !bodyEl ||
        !ctaSlot ||
        !stepLabel ||
        !dots ||
        !prevBtn ||
        !nextBtn ||
        !skipBtn ||
        !mainTour
    ) {
        return;
    }

    let index = 0;
    const total = panels.length;

    function setDots() {
        dots.innerHTML = '';
        for (let i = 0; i < total; i += 1) {
            const dot = document.createElement('span');
            dot.className =
                i === index
                    ? 'h-2 w-6 rounded-full bg-primary transition-[width,background-color] duration-200'
                    : 'h-2 w-2 rounded-full bg-border transition-[width,background-color] duration-200';
            dot.setAttribute('aria-hidden', 'true');
            dots.appendChild(dot);
        }
    }

    function renderPanelContent(panel) {
        titleEl.textContent = panel.title ?? '';
        bodyEl.textContent = panel.body ?? '';
        stepLabel.textContent = `Step ${index + 1} of ${total}`;

        ctaSlot.innerHTML = '';
        const ctaLabel = panel.cta_label;
        const ctaUrl = panel.cta_url;
        if (ctaLabel && ctaUrl) {
            const a = document.createElement('a');
            a.href = ctaUrl;
            a.className =
                'text-sm font-medium text-primary underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';
            a.textContent = ctaLabel;
            ctaSlot.appendChild(a);
        }

        if (live) {
            live.textContent = `${panel.title ?? ''}. ${panel.body ?? ''}`;
        }
    }

    function setVisible(visible) {
        if (reduceMotion) {
            tooltip.classList.toggle('opacity-0', !visible);
            tooltip.classList.toggle('opacity-100', visible);
            tooltip.classList.remove('translate-y-1');
            return;
        }
        tooltip.classList.toggle('translate-y-1', !visible);
        tooltip.classList.toggle('opacity-0', !visible);
        tooltip.classList.toggle('opacity-100', visible);
        tooltip.classList.toggle('translate-y-0', visible);
    }

    function animateStepChange(nextIndex, done) {
        if (reduceMotion) {
            index = nextIndex;
            renderPanelContent(panels[index]);
            setDots();
            done();
            return;
        }
        setVisible(false);
        window.setTimeout(() => {
            index = nextIndex;
            renderPanelContent(panels[index]);
            setDots();
            window.requestAnimationFrame(() => {
                setVisible(true);
                done();
            });
        }, 180);
    }

    function isLastStep() {
        return index >= total - 1;
    }

    function updateChrome() {
        prevBtn.hidden = index <= 0;
        const isLast = isLastStep();
        nextBtn.textContent = isLast ? 'Done' : 'Next';
        skipBtn.hidden = isLast;
    }

    function focusPrimary() {
        nextBtn?.focus();
    }

    function showTour() {
        index = 0;
        mainTour.hidden = false;
        if (replayRow) {
            replayRow.hidden = true;
        }
        renderPanelContent(panels[index]);
        setDots();
        updateChrome();
        if (!reduceMotion) {
            tooltip.classList.add('translate-y-1', 'opacity-0');
            tooltip.classList.remove('translate-y-0', 'opacity-100');
        }
        window.requestAnimationFrame(() => {
            setVisible(true);
            focusPrimary();
        });
    }

    function dismissTour() {
        setVisible(false);
        window.setTimeout(() => {
            mainTour.hidden = true;
            if (replayRow) {
                replayRow.hidden = false;
            }
            replayBtn?.focus();
        }, reduceMotion ? 0 : 200);
    }

    prevBtn.addEventListener('click', () => {
        if (index <= 0) {
            return;
        }
        animateStepChange(index - 1, () => {
            updateChrome();
            prevBtn.focus();
        });
    });

    nextBtn.addEventListener('click', () => {
        if (isLastStep()) {
            dismissTour();
            return;
        }
        animateStepChange(index + 1, () => {
            updateChrome();
            nextBtn.focus();
        });
    });

    skipBtn.addEventListener('click', () => {
        dismissTour();
    });

    replayBtn?.addEventListener('click', () => {
        showTour();
    });

    tooltip.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab' || mainTour.hidden) {
            return;
        }
        const focusables = listFocusable(tooltip);
        if (focusables.length === 0) {
            return;
        }
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    root.addEventListener('keydown', (e) => {
        if (mainTour.hidden) {
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            dismissTour();
        }
    });

    renderPanelContent(panels[index]);
    setDots();
    updateChrome();
    if (!reduceMotion) {
        tooltip.classList.add('translate-y-1', 'opacity-0');
        tooltip.classList.remove('translate-y-0', 'opacity-100');
        window.requestAnimationFrame(() => {
            setVisible(true);
            focusPrimary();
        });
    } else {
        setVisible(true);
        focusPrimary();
    }
}

function listFocusable(container) {
    if (!container) {
        return [];
    }
    return Array.from(container.querySelectorAll(focusableSelector)).filter((el) => {
        if (el.hasAttribute('disabled')) {
            return false;
        }
        return el.getClientRects().length > 0;
    });
}
