@props([
    'action',
    'method' => 'POST',
    'buttonLabel' => 'Delete',
    'confirmMessage' => 'Are you sure you want to proceed?',
    'reconfirmMessage' => 'Please confirm again — do you want to proceed with this action?',
    'iconOnly' => false,
    'iconAriaLabel' => null,
])

@php
    $methodUpper = strtoupper($method);
    $idBase = str_replace('-', '', (string) \Illuminate\Support\Str::uuid());
    $rootId = 'portal-destructive-root-'.$idBase;
    $dialogId = 'portal-destructive-dialog-'.$idBase;
    $isIconOnly = filter_var($iconOnly, FILTER_VALIDATE_BOOLEAN);
    $iconLabel = $iconAriaLabel ?: $buttonLabel;
@endphp

<div data-wff-form-loading-root {{ $attributes->class(['relative', 'inline-block' => $isIconOnly]) }}>
<div
    id="{{ $rootId }}"
    role="group"
    class="contents"
    data-portal-destructive-root
    data-portal-destructive-action="{{ $action }}"
    data-portal-destructive-method="{{ $methodUpper }}"
    data-portal-destructive-token="{{ e(csrf_token()) }}"
>
    @if ($isIconOnly)
        <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-destructive/40"
            data-portal-destructive-open
            aria-haspopup="dialog"
            aria-controls="{{ $dialogId }}"
            aria-label="{{ $iconLabel }}"
            title="{{ $iconLabel }}"
        >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    @else
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-lg border border-destructive/50 bg-destructive/5 px-5 py-2.5 text-sm font-medium text-destructive hover:bg-destructive/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-destructive/40"
            data-portal-destructive-open
            aria-haspopup="dialog"
            aria-controls="{{ $dialogId }}"
        >{{ $buttonLabel }}</button>
    @endif
</div>
<x-form.loading-overlay message="Please wait…" />
</div>

<dialog
    id="{{ $dialogId }}"
    data-portal-destructive-dialog
    data-associated-root="{{ $rootId }}"
    aria-labelledby="{{ $dialogId }}-heading-1"
    class="fixed inset-0 z-[100] m-0 hidden w-full max-w-none border-0 bg-transparent p-4 backdrop:bg-black/50 open:flex open:items-center open:justify-center"
>
    <div class="portal-card flex max-h-[min(90vh,32rem)] w-full max-w-md flex-col overflow-y-auto rounded-xl border border-border bg-background p-0 text-foreground shadow-xl">
        <div class="border-b border-border px-6 pb-4 pt-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Step <span data-step-indicator>1</span> of 2</p>
        </div>

        <div data-step="1" class="flex flex-col gap-5 px-6 py-5">
            <div class="rounded-lg border border-destructive/25 bg-destructive/5 p-4">
                <div class="flex gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/15 text-destructive" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 id="{{ $dialogId }}-heading-1" class="text-lg font-semibold text-foreground">Confirm this action</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ $confirmMessage }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                <button type="button" class="inline-flex items-center justify-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium text-foreground hover:bg-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary/30" data-portal-destructive-close>
                    Cancel
                </button>
                <button type="button" class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary-hover hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary/40" data-portal-destructive-next>
                    Continue
                </button>
            </div>
        </div>

        <div data-step="2" class="hidden flex flex-col gap-5 px-6 py-5" aria-labelledby="{{ $dialogId }}-heading-2">
            <div class="rounded-lg border border-destructive/40 bg-destructive/10 p-4">
                <div class="flex gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/20 text-destructive" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 id="{{ $dialogId }}-heading-2" class="text-lg font-semibold text-destructive">Please confirm again</h2>
                        <p class="mt-2 text-sm leading-relaxed text-foreground">{{ $reconfirmMessage }}</p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-muted-foreground">If you are unsure, use <span class="font-medium text-foreground">Go back</span> to review the first step.</p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between sm:gap-3">
                <button type="button" class="inline-flex items-center justify-center rounded-lg border border-border px-5 py-2.5 text-sm font-medium text-foreground hover:bg-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary/30" data-portal-destructive-back>
                    Go back
                </button>
                <button type="button" class="inline-flex items-center justify-center rounded-lg bg-destructive px-5 py-2.5 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-destructive/60" data-portal-destructive-final>
                    {{ $buttonLabel }}
                </button>
            </div>
        </div>
    </div>
</dialog>

@once
    @push('scripts')
        <script>
            (function () {
                if (window.__portalDestructiveDialogInit) {
                    return;
                }
                window.__portalDestructiveDialogInit = true;

                function showStep(dialog, step) {
                    dialog.querySelectorAll('[data-step]').forEach(function (el) {
                        var n = el.getAttribute('data-step');
                        el.classList.toggle('hidden', n !== String(step));
                    });
                    var ind = dialog.querySelector('[data-step-indicator]');
                    if (ind) {
                        ind.textContent = String(step);
                    }
                }

                function toggleDestructiveLoading(root, show) {
                    var overlay = root ? root.querySelector('[data-wff-form-loading-overlay]') : null;
                    if (!overlay) {
                        return;
                    }
                    if (show) {
                        overlay.classList.remove('hidden');
                        overlay.classList.add('flex');
                        overlay.setAttribute('aria-hidden', 'false');
                    } else {
                        overlay.classList.add('hidden');
                        overlay.classList.remove('flex');
                        overlay.setAttribute('aria-hidden', 'true');
                    }
                }

                function submitDestructiveRoot(root) {
                    var action = root.getAttribute('data-portal-destructive-action') || '';
                    var token = root.getAttribute('data-portal-destructive-token') || '';
                    var method = (root.getAttribute('data-portal-destructive-method') || 'POST').toUpperCase();
                    if (!action || !token) {
                        return;
                    }
                    var body = new URLSearchParams();
                    body.append('_token', token);
                    if (method !== 'GET' && method !== 'POST') {
                        body.append('_method', method);
                    }
                    var wrap = root.closest('[data-wff-form-loading-root]');
                    toggleDestructiveLoading(wrap, true);
                    fetch(action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            Accept: 'text/html, application/xhtml+xml',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: body.toString(),
                        credentials: 'same-origin',
                    })
                        .then(function (res) {
                            if (res.ok) {
                                window.location.assign(res.url || window.location.href);
                                return;
                            }
                            if (res.status === 419) {
                                window.alert('Your session has expired. Refresh the page and try again.');
                                return;
                            }
                            window.alert('This action could not be completed. Please try again.');
                        })
                        .catch(function () {
                            window.alert('Network error. Check your connection and try again.');
                        })
                        .finally(function () {
                            toggleDestructiveLoading(wrap, false);
                        });
                }

                document.addEventListener('click', function (e) {
                    var openBtn = e.target.closest('[data-portal-destructive-open]');
                    if (openBtn) {
                        var id = openBtn.getAttribute('aria-controls');
                        var dialog = id ? document.getElementById(id) : null;
                        if (!dialog || typeof dialog.showModal !== 'function') {
                            return;
                        }
                        e.preventDefault();
                        showStep(dialog, 1);
                        dialog.showModal();
                        return;
                    }

                    if (e.target.closest('[data-portal-destructive-next]')) {
                        var dlg = e.target.closest('dialog[data-portal-destructive-dialog]');
                        if (dlg) {
                            showStep(dlg, 2);
                        }
                        return;
                    }

                    if (e.target.closest('[data-portal-destructive-back]')) {
                        var dlgB = e.target.closest('dialog[data-portal-destructive-dialog]');
                        if (dlgB) {
                            showStep(dlgB, 1);
                        }
                        return;
                    }

                    if (e.target.closest('[data-portal-destructive-close]')) {
                        var dlgC = e.target.closest('dialog[data-portal-destructive-dialog]');
                        if (dlgC && typeof dlgC.close === 'function') {
                            dlgC.close();
                        }
                        return;
                    }

                    if (e.target.closest('[data-portal-destructive-final]')) {
                        var dlgF = e.target.closest('dialog[data-portal-destructive-dialog]');
                        if (!dlgF) {
                            return;
                        }
                        var rootId = dlgF.getAttribute('data-associated-root');
                        var root = rootId ? document.getElementById(rootId) : null;
                        if (!root || !root.hasAttribute('data-portal-destructive-action')) {
                            return;
                        }
                        dlgF.close();
                        submitDestructiveRoot(root);
                    }
                });

                document.querySelectorAll('dialog[data-portal-destructive-dialog]').forEach(function (dialog) {
                    dialog.addEventListener('close', function () {
                        showStep(dialog, 1);
                    });
                });
            })();
        </script>
    @endpush
@endonce
