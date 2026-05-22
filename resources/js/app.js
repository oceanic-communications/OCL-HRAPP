import './bootstrap';
import { initWffFormLoading } from './wff-form-loading';
import { initApplicantWizardCharCount } from './applicant-wizard-char-count';
import { initApplicantHeadSignature } from './applicant-head-signature';
import {
    initApplicantWizardClientValidation,
    initApplicantWizardConditionalVisibility,
} from './applicant-wizard-client-validation';
import { initApplicantWizardGrantRange } from './applicant-wizard-grant-range';
import { initApplicantWizardAutoSave } from './applicant-wizard-auto-save';
import { initHelpWalkthrough } from './help-walkthrough';
import { initPortalAutoGrowTextareas } from './portal-auto-grow-textarea';
import { initPortalDatepickers } from './portal-datepicker';
import { initInductionSignature } from './induction-signature';
import { initRoleSetupForm } from './role-setup-form';
import './induction-section-save';
import './portal-sidebar-nav-group';
import './policy-document-builder';

const focusableSelector =
    'a[href], button:not([disabled]), textarea, input:not([type="hidden"]), select, [tabindex]:not([tabindex="-1"])';

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

document.addEventListener('DOMContentLoaded', () => {
    initPortalDatepickers();
    initWffFormLoading();
    initRoleSetupForm();

    document.querySelectorAll('form[data-induction-form]').forEach((form) => {
        if (form instanceof HTMLFormElement) {
            initInductionSignature(form);
        }
    });

    const applicantGrantRangeForm = document.querySelector('form[data-applicant-grant-range]');
    if (applicantGrantRangeForm) {
        initApplicantWizardGrantRange(applicantGrantRangeForm);
    }

    const applicantWizardCharForm = document.querySelector('form[data-applicant-wizard-char-count]');
    if (applicantWizardCharForm) {
        // Auto-grow upgrades <input data-portal-auto-grow> → <textarea> before char/word counts run.
        initPortalAutoGrowTextareas(applicantWizardCharForm);
        initApplicantWizardCharCount(applicantWizardCharForm);
        initApplicantWizardConditionalVisibility(applicantWizardCharForm);
        if (applicantWizardCharForm.querySelector('[data-applicant-head-signature]')) {
            initApplicantHeadSignature(applicantWizardCharForm);
        }
        initApplicantWizardClientValidation(applicantWizardCharForm);
        if (applicantWizardCharForm.hasAttribute('data-applicant-wizard-auto-save')) {
            initApplicantWizardAutoSave(applicantWizardCharForm);
        }
    }

    document.querySelectorAll('[data-applicant-repeatable-text]').forEach((root) => {
        import('./applicant-wizard-repeatable-text.js').then((m) => {
            m.initApplicantRepeatableText(root);
        });
    });

    document.querySelectorAll('[data-applicant-repeatable-group]').forEach((root) => {
        import('./applicant-wizard-repeatable-group.js').then((m) => {
            m.initApplicantRepeatableGroup(root);
        });
    });

    const helpWalkthroughRoot = document.getElementById('help-walkthrough');
    if (helpWalkthroughRoot) {
        initHelpWalkthrough(helpWalkthroughRoot);
    }

    const menuOpenBtn = document.getElementById('portal-menu-open');
    const sidebar = document.getElementById('portal-sidebar');
    const overlay = document.getElementById('portal-sidebar-overlay');
    let sidebarReturnFocus = null;

    const isMobileSidebar = () => window.matchMedia('(max-width: 767px)').matches;

    const syncPortalSidebarA11y = () => {
        if (!sidebar || !menuOpenBtn) {
            return;
        }
        if (isMobileSidebar()) {
            const open = sidebar.classList.contains('is-open');
            sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
            menuOpenBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        } else {
            sidebar.setAttribute('aria-hidden', 'false');
            menuOpenBtn.setAttribute('aria-expanded', 'false');
        }
    };

    const openSidebar = () => {
        sidebar?.classList.add('is-open');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        sidebarReturnFocus = document.activeElement;
        syncPortalSidebarA11y();
        if (isMobileSidebar() && sidebar) {
            const focusables = listFocusable(sidebar);
            const prefer = document.getElementById('portal-sidebar-close');
            const target = prefer && focusables.includes(prefer) ? prefer : focusables[0];
            window.requestAnimationFrame(() => target?.focus());
        }
    };

    const closeSidebar = () => {
        sidebar?.classList.remove('is-open');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        syncPortalSidebarA11y();
        if (sidebarReturnFocus && typeof sidebarReturnFocus.focus === 'function') {
            sidebarReturnFocus.focus();
        }
        sidebarReturnFocus = null;
    };

    menuOpenBtn?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);
    document.getElementById('portal-sidebar-close')?.addEventListener('click', closeSidebar);

    document.querySelectorAll('.portal-sidebar-link').forEach((link) => {
        link.addEventListener('click', () => closeSidebar());
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 768px)').matches) {
            closeSidebar();
        } else {
            syncPortalSidebarA11y();
        }
    });

    syncPortalSidebarA11y();

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || !sidebar?.classList.contains('is-open') || !isMobileSidebar()) {
            return;
        }
        closeSidebar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab' || !sidebar?.classList.contains('is-open') || !isMobileSidebar()) {
            return;
        }
        const focusables = listFocusable(sidebar);
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

    document.addEventListener('focusin', (e) => {
        if (!sidebar?.classList.contains('is-open') || !isMobileSidebar()) {
            return;
        }
        if (sidebar.contains(e.target)) {
            return;
        }
        window.requestAnimationFrame(() => {
            if (!sidebar.classList.contains('is-open')) {
                return;
            }
            const focusables = listFocusable(sidebar);
            focusables[0]?.focus();
        });
    });

    const formatGrantFunding = (value) =>
        '$' +
        Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });

    const applyGrantIndexStats = (host, tabName, tabsRoot) => {
        if (!host || !tabsRoot) {
            return;
        }
        const btn = Array.from(tabsRoot.querySelectorAll('[data-tab-button]')).find(
            (b) => b.getAttribute('data-tab-button') === tabName,
        );
        if (!btn) {
            return;
        }
        const totalRaw = btn.getAttribute('data-filter-stat-total');
        const activeRaw = btn.getAttribute('data-filter-stat-active');
        const notActiveRaw = btn.getAttribute('data-filter-stat-not-active');
        if (totalRaw === null || activeRaw === null || notActiveRaw === null) {
            return;
        }
        const totalEl = host.querySelector('[data-stat-total-funding]');
        const activeEl = host.querySelector('[data-stat-active-count]');
        const notActiveEl = host.querySelector('[data-stat-not-active-count]');
        if (totalEl) {
            totalEl.textContent = formatGrantFunding(Number(totalRaw));
        }
        if (activeEl) {
            activeEl.textContent = String(Math.max(0, Math.round(Number(activeRaw)) || 0));
        }
        if (notActiveEl) {
            notActiveEl.textContent = String(Math.max(0, Math.round(Number(notActiveRaw)) || 0));
        }
    };

    const initPortalTabs = (root) => {
        const buttons = Array.from(root.querySelectorAll('[data-tab-button]'));
        const panels = Array.from(root.querySelectorAll('[data-tab-panel]'));
        const tablist =
            root.querySelector('[data-tablist]') ?? buttons[0]?.parentElement ?? null;
        if (!tablist || buttons.length === 0 || panels.length === 0) {
            return;
        }

        const prefix = `wff-tab-${Math.random().toString(36).slice(2, 11)}`;
        const tablistLabel = root.getAttribute('data-tabs-label') || 'Sections';

        tablist.setAttribute('role', 'tablist');
        tablist.setAttribute('aria-label', tablistLabel);

        buttons.forEach((btn) => {
            const name = btn.getAttribute('data-tab-button');
            const tabId = `${prefix}-t-${name}`;
            const panelId = `${prefix}-p-${name}`;
            btn.id = tabId;
            btn.setAttribute('role', 'tab');
            btn.setAttribute('aria-controls', panelId);
            const panel = panels.find((p) => p.getAttribute('data-tab-panel') === name);
            if (panel) {
                panel.id = panelId;
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('aria-labelledby', tabId);
            }
        });

        const statsHost = root.closest('[data-grants-index]');

        const activate = (name, { focusTab = false } = {}) => {
            buttons.forEach((btn) => {
                const active = btn.getAttribute('data-tab-button') === name;
                btn.setAttribute('data-active', active ? 'true' : 'false');
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
                btn.tabIndex = active ? 0 : -1;
            });
            panels.forEach((panel) => {
                const isMatch = panel.getAttribute('data-tab-panel') === name;
                panel.classList.toggle('hidden', !isMatch);
                panel.toggleAttribute('hidden', !isMatch);
            });
            applyGrantIndexStats(statsHost, name, root);
            if (focusTab) {
                const tab = buttons.find((b) => b.getAttribute('data-tab-button') === name);
                tab?.focus();
            }
        };

        const initial = root.getAttribute('data-tabs') || buttons[0]?.getAttribute('data-tab-button');
        if (initial) {
            activate(initial);
        }

        tablist.addEventListener('keydown', (e) => {
            const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
            if (!keys.includes(e.key)) {
                return;
            }
            let idx = buttons.findIndex((b) => b.getAttribute('aria-selected') === 'true');
            if (idx < 0) {
                idx = 0;
            }
            if (e.key === 'Home') {
                e.preventDefault();
                activate(buttons[0].getAttribute('data-tab-button'), { focusTab: true });
                return;
            }
            if (e.key === 'End') {
                e.preventDefault();
                activate(buttons[buttons.length - 1].getAttribute('data-tab-button'), { focusTab: true });
                return;
            }
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                const dir = e.key === 'ArrowRight' ? 1 : -1;
                const next = (idx + dir + buttons.length) % buttons.length;
                activate(buttons[next].getAttribute('data-tab-button'), { focusTab: true });
            }
        });

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => activate(btn.getAttribute('data-tab-button')));
        });
    };

    document.querySelectorAll('[data-tabs]').forEach((root) => initPortalTabs(root));
});
