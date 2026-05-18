document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('induction-section-edit-form');
    const dialog = document.querySelector('[data-induction-staff-dialog]');
    if (!(form instanceof HTMLFormElement) || !(dialog instanceof HTMLDialogElement)) {
        return;
    }

    const openBtn = form.querySelector('[data-induction-save-open]');
    const confirmBtn = dialog.querySelector('[data-induction-staff-confirm]');
    const cancelBtn = dialog.querySelector('[data-induction-staff-cancel]');
    const hidden = form.querySelector('input[name="staff_must_repeat_induction"]');
    const errorEl = dialog.querySelector('[data-induction-staff-error]');

    const showError = (show) => {
        errorEl?.classList.toggle('hidden', !show);
    };

    if (document.querySelector('[data-induction-staff-validation-error]') && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }

    openBtn?.addEventListener('click', () => {
        if (!form.reportValidity()) {
            return;
        }
        showError(false);
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
    });

    cancelBtn?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', (e) => {
        e.preventDefault();
        dialog.close();
    });

    confirmBtn?.addEventListener('click', () => {
        const selected = dialog.querySelector('input[name="dialog_staff_effect"]:checked');
        if (!(selected instanceof HTMLInputElement)) {
            showError(true);
            return;
        }
        if (hidden instanceof HTMLInputElement) {
            hidden.value = selected.value;
        }
        dialog.close();
        form.submit();
    });
});
