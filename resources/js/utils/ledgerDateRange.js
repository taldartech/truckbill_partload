/**
 * Keep ledger report date filters within a maximum 1-month span.
 */
export function thisMonthDateRange() {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    return {
        start_date: toDateInput(start),
        end_date: toDateInput(end),
    };
}

export function clampLedgerDateRange(form, changedField) {
    if (!form.start_date && !form.end_date) {
        const range = thisMonthDateRange();
        form.start_date = range.start_date;
        form.end_date = range.end_date;
        return;
    }

    if (form.start_date && !form.end_date) {
        form.end_date = addMonths(form.start_date, 1);
        return;
    }

    if (!form.start_date && form.end_date) {
        form.start_date = addMonths(form.end_date, -1);
        return;
    }

    const start = parseDate(form.start_date);
    const end = parseDate(form.end_date);
    if (!start || !end) {
        return;
    }

    if (end < start) {
        if (changedField === 'start_date') {
            form.end_date = form.start_date;
        } else {
            form.start_date = form.end_date;
        }
        return;
    }

    const maxEnd = addMonthsDate(start, 1);
    if (end > maxEnd) {
        if (changedField === 'start_date') {
            form.end_date = toDateInput(maxEnd);
        } else {
            form.start_date = toDateInput(addMonthsDate(end, -1));
        }
    }
}

function toDateInput(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function parseDate(value) {
    if (!value) return null;
    const [y, m, d] = value.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}

function addMonths(value, months) {
    return toDateInput(addMonthsDate(parseDate(value), months));
}

function addMonthsDate(date, months) {
    const next = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const day = next.getDate();
    next.setMonth(next.getMonth() + months);
    // Clamp overflow days (e.g. Jan 31 + 1 month).
    if (next.getDate() !== day) {
        next.setDate(0);
    }
    return next;
}
