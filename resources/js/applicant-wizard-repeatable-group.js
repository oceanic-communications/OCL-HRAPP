import { initPortalAutoGrowTextareas } from './portal-auto-grow-textarea';
import { initPortalDatepickers } from './portal-datepicker';

/**
 * Set readonly row-sequence inputs to 1..n by DOM order.
 *
 * @param {ParentNode} rowsHost
 */
function syncRepeatableGroupRowNumbers(rowsHost) {
    const rows = rowsHost.querySelectorAll('[data-repeatable-group-row]');
    rows.forEach((row, idx) => {
        const n = String(idx + 1);
        row.querySelectorAll('[data-repeatable-auto-row-number]').forEach((inp) => {
            if (inp instanceof HTMLInputElement) {
                inp.value = n;
            }
        });
    });
}

/**
 * Add / remove rows for applicant wizard repeatable_group fields (structured columns per row).
 *
 * @param {HTMLElement} root
 */
export function initApplicantRepeatableGroup(root) {
    const minRows = Math.max(1, Number.parseInt(root.dataset.repeatableMin || '1', 10) || 1);
    const maxRowsRaw = root.dataset.repeatableMax;
    const maxRows =
        maxRowsRaw !== undefined && maxRowsRaw !== ''
            ? Math.max(1, Number.parseInt(maxRowsRaw, 10) || 0)
            : null;
    const fieldName = root.dataset.repeatableField || '';
    const rowsHost = root.querySelector('.repeatable-group-rows');
    const tpl = document.getElementById(`answers_${fieldName}_row_tpl`);
    const addBtn = root.querySelector('.add-repeatable-group-row');

    if (!rowsHost || !tpl?.content || !fieldName) {
        return;
    }

    function updateRemoveButtons() {
        const rows = rowsHost.querySelectorAll('[data-repeatable-group-row]');
        rows.forEach((row) => {
            const btn = row.querySelector('.remove-repeatable-group-row');
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

    function updateAddRowButton() {
        if (!addBtn || maxRows === null) {
            return;
        }
        const rows = rowsHost.querySelectorAll('[data-repeatable-group-row]');
        if (rows.length >= maxRows) {
            addBtn.classList.add('hidden');
        } else {
            addBtn.classList.remove('hidden');
        }
    }

    /**
     * @param {ParentNode} frag
     * @param {string} indexStr
     */
    function applyRowIndex(frag, indexStr) {
        frag.querySelectorAll('[name*="RGIDX"]').forEach((el) => {
            const n = el.getAttribute('name');
            if (n) {
                el.setAttribute('name', n.replaceAll('RGIDX', indexStr));
            }
        });
        frag.querySelectorAll('[id*="RGIDX"]').forEach((el) => {
            const id = el.getAttribute('id');
            if (id) {
                el.setAttribute('id', id.replaceAll('RGIDX', indexStr));
            }
        });
        frag.querySelectorAll('input, textarea, select').forEach((el) => {
            if (el instanceof HTMLSelectElement) {
                el.selectedIndex = 0;
            } else if (el instanceof HTMLTextAreaElement || el instanceof HTMLInputElement) {
                if (el.type === 'checkbox') {
                    el.checked = false;
                } else if (el.type !== 'file') {
                    el.value = '';
                }
            }
            el.removeAttribute('aria-invalid');
            el.classList.remove('border-destructive');
        });
    }

    function nextNumericIndex() {
        let max = -1;
        const esc = fieldName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const re = new RegExp(`^answers\\[${esc}\\]\\[(\\d+)\\]`);
        rowsHost.querySelectorAll('input[name], textarea[name], select[name]').forEach((el) => {
            const name = el.getAttribute('name') || '';
            const m = name.match(re);
            if (m) {
                max = Math.max(max, Number.parseInt(m[1], 10) || 0);
            }
        });
        return max + 1;
    }

    addBtn?.addEventListener('click', () => {
        if (maxRows !== null && rowsHost.querySelectorAll('[data-repeatable-group-row]').length >= maxRows) {
            return;
        }
        const idx = String(nextNumericIndex());
        const frag = tpl.content.cloneNode(true);
        applyRowIndex(frag, idx);
        rowsHost.appendChild(frag);
        initPortalAutoGrowTextareas(rowsHost);
        initPortalDatepickers();
        syncRepeatableGroupRowNumbers(rowsHost);
        updateRemoveButtons();
        updateAddRowButton();
        const rowNodes = rowsHost.querySelectorAll('[data-repeatable-group-row]');
        const lastRow = rowNodes[rowNodes.length - 1];
        const first = lastRow?.querySelector('input, textarea, select') ?? null;
        first?.dispatchEvent(new Event('input', { bubbles: true }));
        first?.focus();
    });

    rowsHost.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-repeatable-group-row');
        if (!btn || btn.disabled) {
            return;
        }
        const row = btn.closest('[data-repeatable-group-row]');
        if (!row) {
            return;
        }
        if (rowsHost.querySelectorAll('[data-repeatable-group-row]').length <= minRows) {
            return;
        }
        row.remove();
        syncRepeatableGroupRowNumbers(rowsHost);
        updateRemoveButtons();
        updateAddRowButton();
    });

    syncRepeatableGroupRowNumbers(rowsHost);
    updateRemoveButtons();
    updateAddRowButton();
}
