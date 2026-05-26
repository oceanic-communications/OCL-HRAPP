function wordCountFromHtml(html) {
    const doc = new DOMParser().parseFromString('<div>' + (html || '') + '</div>', 'text/html');
    const text = (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
    return text ? text.split(/\s+/).length : 0;
}

function hasTextContent(html) {
    return wordCountFromHtml(html) > 0;
}

function syncEditor(textarea) {
    if (typeof window.tinymce === 'undefined') {
        return;
    }
    const editor = window.tinymce.get(textarea.id);
    if (editor) {
        editor.save();
    }
}

function syncAll() {
    document.querySelectorAll('[data-rich-editor]').forEach(syncEditor);
}

function showClientError(textarea, message) {
    const errorEl = textarea
        .closest('div')
        ?.querySelector('[data-rich-editor-error]');
    if (!(errorEl instanceof HTMLElement)) {
        return;
    }
    if (message) {
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    } else {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    }
}

function updateCounter(textarea) {
    const countEl = textarea
        .closest('div')
        ?.querySelector('[data-rich-editor-count]');
    if (!(countEl instanceof HTMLElement)) {
        return;
    }

    syncEditor(textarea);
    const maxWords = parseInt(textarea.dataset.maxWords || '0', 10) || 0;
    const words = wordCountFromHtml(textarea.value);
    countEl.textContent = maxWords > 0
        ? `${words.toLocaleString()} / ${maxWords.toLocaleString()} words`
        : `${words.toLocaleString()} words`;

    if (maxWords > 0 && words > maxWords) {
        countEl.classList.add('text-destructive');
    } else {
        countEl.classList.remove('text-destructive');
    }
}

function validateTextarea(textarea) {
    syncEditor(textarea);
    const maxWords = parseInt(textarea.dataset.maxWords || '0', 10) || 0;
    const words = wordCountFromHtml(textarea.value);
    const required = textarea.hasAttribute('required');

    if (required && !hasTextContent(textarea.value)) {
        showClientError(textarea, 'Content is required.');
        return false;
    }

    if (maxWords > 0 && words > maxWords) {
        showClientError(textarea, `Maximum ${maxWords.toLocaleString()} words.`);
        return false;
    }

    showClientError(textarea, '');
    return true;
}

function validateForm(form) {
    const fields = form.querySelectorAll('[data-rich-editor]');
    let valid = true;

    fields.forEach((field) => {
        if (!(field instanceof HTMLTextAreaElement)) {
            return;
        }
        if (!validateTextarea(field)) {
            valid = false;
            if (typeof window.tinymce !== 'undefined') {
                window.tinymce.get(field.id)?.focus();
            }
        }
    });

    return valid;
}

function initRichEditor(textarea) {
    if (!(textarea instanceof HTMLTextAreaElement) || textarea.dataset.richEditorInit === '1') {
        return;
    }

    if (typeof window.tinymce === 'undefined') {
        return;
    }

    textarea.dataset.richEditorInit = '1';
    const form = textarea.closest('form');

    window.tinymce.init({
        selector: '#' + textarea.id,
        license_key: 'gpl',
        height: 360,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'link lists autoresize code media table',
        toolbar:
            'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | ' +
            'bullist numlist | link media table | removeformat | code',
        relative_urls: false,
        convert_urls: false,
        setup(editor) {
            editor.on('change keyup undo redo', () => updateCounter(textarea));
            editor.on('init', () => updateCounter(textarea));
        },
    });

    if (form instanceof HTMLFormElement) {
        form.addEventListener('submit', (event) => {
            syncAll();
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });
    }

    updateCounter(textarea);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-rich-editor]').forEach((el) => {
        if (el instanceof HTMLTextAreaElement) {
            initRichEditor(el);
        }
    });
});

window.inductionRichEditor = {
    syncAll,
    validateForm,
    wordCountFromHtml,
};
