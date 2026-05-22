/**
 * Policy document builder — numbering popover preview & settings pills.
 */
function formatSubIndex(style, index) {
    const i = Math.max(1, index);
    if (style === 'alpha_upper') {
        return String.fromCharCode(64 + i);
    }
    if (style === 'alpha_lower') {
        return String.fromCharCode(96 + i);
    }
    if (style === 'roman') {
        const map = [
            [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I'],
        ];
        let n = i;
        let out = '';
        for (const [v, s] of map) {
            while (n >= v) {
                out += s;
                n -= v;
            }
        }
        return out || String(i);
    }
    return String(i);
}

function updateNumberingPreview(root) {
    const popover = root.querySelector('[data-numbering-popover]');
    if (!popover) {
        return;
    }
    const preview = popover.querySelector('[data-numbering-preview]');
    const prefix = popover.querySelector('[data-numbering-prefix]');
    const style = popover.querySelector('[data-numbering-style]');
    const separator = popover.querySelector('[data-numbering-separator]');
    if (!preview || !prefix || !style || !separator) {
        return;
    }

    const builder = root.closest('[data-policy-builder]');
    const clausePart = builder?.dataset.clausePart || '';
    const title = builder?.dataset.subTitle || '';
    const sep = separator.value || '.';
    const subPart = formatSubIndex(style.value, 2);
    const pre = prefix.value || '';

    preview.textContent = `${pre}${clausePart}${sep}${subPart} ${title}`.trim();
}

export function initPolicyDocumentBuilder(root = document) {
    const builder = root.querySelector('[data-policy-builder]');
    if (builder) {
        builder.querySelectorAll('[data-numbering-prefix], [data-numbering-style], [data-numbering-separator]').forEach((el) => {
            el.addEventListener('input', () => updateNumberingPreview(builder));
            el.addEventListener('change', () => updateNumberingPreview(builder));
        });
        updateNumberingPreview(builder);

        builder.querySelectorAll('[data-sub-clause-node]').forEach((node) => {
            node.addEventListener('click', () => {
                builder.dataset.clausePart = node.dataset.clausePart || '';
                builder.dataset.subTitle = node.dataset.subTitle || '';
                updateNumberingPreview(builder);
            });
        });
    }

    root.querySelectorAll('[data-preview-pill]').forEach((pill) => {
        pill.addEventListener('click', () => {
            const group = pill.parentElement;
            if (!group) {
                return;
            }
            group.querySelectorAll('[data-preview-pill]').forEach((p) => p.classList.remove('is-selected'));
            pill.classList.add('is-selected');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPolicyDocumentBuilder(document);
});
