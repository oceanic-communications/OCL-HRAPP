import { initPortalAutoGrowTextareas } from './portal-auto-grow-textarea';

/**
 * Add / remove rows for applicant wizard repeatable_text fields (min row count enforced).
 *
 * @param {HTMLElement} root
 */
export function initApplicantRepeatableText(root) {
    const minRows = Math.max(1, Number.parseInt(root.dataset.repeatableMin || '1', 10) || 1);
    const fieldName = root.dataset.repeatableField || '';
    const placeholder = root.dataset.repeatablePlaceholder || '';
    const maxLen = root.dataset.repeatableMaxlength?.trim() || '';
    const maxWords = root.dataset.repeatableMaxWords?.trim() || '';
    const minWords = root.dataset.repeatableMinWords?.trim() || '';
    const rowsHost = root.querySelector('.repeatable-rows');
    const tpl = document.getElementById(`answers_${fieldName}_row_tpl`);

    if (!rowsHost || !tpl?.content) {
        return;
    }

    function updateRemoveButtons() {
        const rows = rowsHost.querySelectorAll('.objective-row');
        rows.forEach((row) => {
            const btn = row.querySelector('.remove-repeatable-row');
            if (btn) {
                const disable = rows.length <= minRows;
                btn.disabled = disable;
                if (disable) {
                    btn.setAttribute('aria-disabled', 'true');
                } else {
                    btn.removeAttribute('aria-disabled');
                }
            }
        });
    }

    root.querySelector('.add-repeatable-row')?.addEventListener('click', () => {
        const frag = tpl.content.cloneNode(true);
        const control = frag.querySelector('textarea[data-portal-auto-grow], input[type="text"][data-portal-auto-grow]');
        if (control) {
            control.value = '';
            control.name = `answers[${fieldName}][]`;
            control.placeholder = placeholder;
            control.removeAttribute('aria-invalid');
            control.classList.remove('border-destructive');
            control.id = `answers_${fieldName}_r_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
            if (maxLen !== '') {
                control.setAttribute('maxlength', maxLen);
            } else {
                control.removeAttribute('maxlength');
            }
            if (maxWords !== '') {
                control.setAttribute('data-max-words', maxWords);
            } else {
                control.removeAttribute('data-max-words');
            }
            if (minWords !== '') {
                control.setAttribute('data-min-words', minWords);
            } else {
                control.removeAttribute('data-min-words');
            }
        }
        rowsHost.appendChild(frag);
        initPortalAutoGrowTextareas(rowsHost);
        updateRemoveButtons();
        control?.dispatchEvent(new Event('input', { bubbles: true }));
        control?.focus();
    });

    rowsHost.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-repeatable-row');
        if (!btn || btn.disabled) {
            return;
        }
        const row = btn.closest('.objective-row');
        if (!row) {
            return;
        }
        if (rowsHost.querySelectorAll('.objective-row').length <= minRows) {
            return;
        }
        row.remove();
        updateRemoveButtons();
    });

    updateRemoveButtons();
}
