/**
 * Expand/collapse sidebar navigation groups (e.g. Policies sub-menu).
 */
export function initPortalSidebarNavGroups(root = document) {
    root.querySelectorAll('[data-portal-nav-group]').forEach((group) => {
        const toggle = group.querySelector('[data-portal-nav-group-toggle]');
        const items = group.querySelector('[data-portal-nav-group-items]');
        const chevron = group.querySelector('[data-portal-nav-chevron]');
        if (!toggle || !items) {
            return;
        }

        toggle.addEventListener('click', () => {
            const hidden = items.classList.toggle('hidden');
            group.classList.toggle('is-open', !hidden);
            toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
            chevron?.classList.toggle('rotate-180', !hidden);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPortalSidebarNavGroups(document);
});
