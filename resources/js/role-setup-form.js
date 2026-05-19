/**
 * Toggle existing vs new permission template fields on the unified role form.
 */
function initRoleSetupForm(root = document) {
    const form = root.querySelector('[data-role-setup-form]');
    if (!form) {
        return;
    }

    const modeInputs = form.querySelectorAll('input[name="template_mode"]');
    const existingPanel = form.querySelector('[data-template-panel="existing"]');
    const newPanel = form.querySelector('[data-template-panel="new"]');

    if (!modeInputs.length || !existingPanel || !newPanel) {
        return;
    }

    const sync = () => {
        const mode = form.querySelector('input[name="template_mode"]:checked')?.value ?? 'existing';
        const isNew = mode === 'new';

        existingPanel.classList.toggle('hidden', isNew);
        newPanel.classList.toggle('hidden', !isNew);

        existingPanel.querySelectorAll('select, input').forEach((el) => {
            el.disabled = isNew;
        });
        newPanel.querySelectorAll('input').forEach((el) => {
            el.disabled = !isNew;
        });
    };

    modeInputs.forEach((input) => input.addEventListener('change', sync));
    sync();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initRoleSetupForm());
} else {
    initRoleSetupForm();
}

export { initRoleSetupForm };
