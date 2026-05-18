import flatpickr from 'flatpickr';

function optionsForInput(input) {
    const altInputClass = `${input.className.replace(/\bportal-datepicker\b/g, '').trim()} !cursor-pointer`.trim();
    const opts = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j F Y',
        allowInput: true,
        altInputClass,
        monthSelectorType: 'static',
        animate: true,
    };
    const raw = input.value?.trim();
    if (raw && /^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        opts.defaultDate = raw;
    }
    return opts;
}

function addCalendarDays(date, days) {
    const d = new Date(date.getTime());
    d.setDate(d.getDate() + days);
    return d;
}

/**
 * Flatpickr may not have populated selectedDates yet; the hidden input still holds Y-m-d from the server.
 *
 * @param {{ selectedDates: Date[], input?: HTMLInputElement }} fp
 * @returns {Date | null}
 */
function calendarDateFromFlatpickrStart(fp) {
    const sel = fp.selectedDates[0];
    if (sel) {
        return sel;
    }
    const raw = fp.input?.value?.trim();
    if (!raw || !/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return null;
    }
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw);
    if (!m) {
        return null;
    }
    const y = Number(m[1]);
    const mo = Number(m[2]);
    const d = Number(m[3]);
    const dt = new Date(y, mo - 1, d);
    return Number.isNaN(dt.getTime()) ? null : dt;
}

/**
 * @param {{ config: Record<string, unknown> }} fp
 * @param {string} hookName
 * @param {(...args: unknown[]) => void} fn
 */
function pushFlatpickrHook(fp, hookName, fn) {
    const cur = fp.config[hookName];
    if (Array.isArray(cur)) {
        cur.push(fn);
        return;
    }
    if (typeof cur === 'function') {
        fp.config[hookName] = [cur, fn];
        return;
    }
    fp.config[hookName] = [fn];
}

function wireProjectStartEndRange() {
    const start = document.querySelector('input.portal-datepicker[name="project_start"]');
    const end = document.querySelector('input.portal-datepicker[name="project_end"]');
    if (!start?._flatpickr || !end?._flatpickr) {
        return;
    }
    const fpStart = start._flatpickr;
    const fpEnd = end._flatpickr;

    function syncRange() {
        const startD = calendarDateFromFlatpickrStart(fpStart);
        const endD = calendarDateFromFlatpickrStart(fpEnd);
        if (startD) {
            fpEnd.set('minDate', startD);
        } else {
            fpEnd.set('minDate', null);
        }
        if (endD) {
            fpStart.set('maxDate', endD);
        } else {
            fpStart.set('maxDate', null);
        }
        if (startD && endD && endD < startD) {
            fpEnd.setDate(startD, false);
        }
    }

    pushFlatpickrHook(fpStart, 'onChange', syncRange);
    pushFlatpickrHook(fpEnd, 'onChange', syncRange);
    syncRange();
}

/**
 * Applicant wizard (and any form): end date min = start date + N calendar days (data attributes on end input).
 */
function wireDateMinAfterFieldPairs() {
    document.querySelectorAll('input.portal-datepicker[data-date-min-after-field]').forEach((endInput) => {
        const refField = endInput.getAttribute('data-date-min-after-field')?.trim();
        if (!refField) {
            return;
        }
        const daysRaw = endInput.getAttribute('data-date-min-after-days');
        const offsetDays = Math.max(0, Number.parseInt(daysRaw ?? '1', 10) || 0);
        const startInput = document.querySelector(
            `input.portal-datepicker[name="answers[${refField}]"]`,
        );
        if (!startInput?._flatpickr || !endInput._flatpickr) {
            return;
        }
        const fpStart = startInput._flatpickr;
        const fpEnd = endInput._flatpickr;

        function minEndDate() {
            const startD = calendarDateFromFlatpickrStart(fpStart);
            if (!startD) {
                return null;
            }
            return addCalendarDays(startD, offsetDays);
        }

        function syncEndMin() {
            const minEnd = minEndDate();
            if (minEnd) {
                fpEnd.set('minDate', minEnd);
            } else {
                fpEnd.set('minDate', null);
            }
            const endD = fpEnd.selectedDates[0] ?? calendarDateFromFlatpickrStart(fpEnd);
            if (minEnd && endD && endD < minEnd) {
                fpEnd.setDate(minEnd, false);
            }
        }

        fpStart.config.onChange.push(syncEndMin);
        fpEnd.config.onChange.push(syncEndMin);
        pushFlatpickrHook(fpStart, 'onValueUpdate', syncEndMin);
        pushFlatpickrHook(fpEnd, 'onOpen', syncEndMin);
        syncEndMin();
    });
}

export function initPortalDatepickers() {
    document.querySelectorAll('input.portal-datepicker').forEach((input) => {
        if (input._flatpickr) {
            return;
        }
        const opts = optionsForInput(input);
        if (input.dataset.dateMinToday === 'true' || input.dataset.dateMinToday === '1') {
            opts.minDate = 'today';
        }
        const fp = flatpickr(input, opts);
        // altInput is the visible control; keep <label for="…"> pointing at it (avoids a11y / DevTools label warnings).
        if (fp?.altInput && input.id) {
            fp.altInput.id = input.id;
            input.removeAttribute('id');
            input.setAttribute('aria-hidden', 'true');
            input.setAttribute('tabindex', '-1');
        }
    });

    // Defer pairing so each instance has parsed defaultDate / server Y-m-d values from the real inputs.
    window.requestAnimationFrame(() => {
        wireProjectStartEndRange();
        window.requestAnimationFrame(() => {
            wireDateMinAfterFieldPairs();
        });
    });
}
