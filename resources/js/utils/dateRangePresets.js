function pad(value) {
    return String(value).padStart(2, '0');
}

export function toIsoDate(date = new Date()) {
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());

    return `${year}-${month}-${day}`;
}

export function addDaysToDateInput(dateInput, days) {
    if (!dateInput) {
        return '';
    }

    const [year, month, day] = dateInput.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    date.setDate(date.getDate() + days);

    return toIsoDate(date);
}

export const DATE_RANGE_PRESETS = [
    { key: 'today', label: 'Today' },
    { key: 'yesterday', label: 'Yesterday' },
    { key: 'this_month', label: 'This Month' },
    { key: 'last_month', label: 'Last Month' },
    { key: 'custom', label: 'Custom Range' },
];

export const BILTY_LIST_DATE_PRESET_KEYS = ['today', 'yesterday', 'this_month', 'last_month'];

export function todayDateRange(referenceDate = new Date()) {
    const iso = toIsoDate(referenceDate);

    return { from_date: iso, to_date: iso };
}

export function yesterdayDateRange(referenceDate = new Date()) {
    const iso = addDaysToDateInput(toIsoDate(referenceDate), -1);

    return { from_date: iso, to_date: iso };
}

export function thisMonthDateRange(referenceDate = new Date()) {
    const today = toIsoDate(referenceDate);
    const [year, month] = today.split('-').map(Number);
    const lastDay = new Date(year, month, 0).getDate();

    return {
        from_date: `${year}-${pad(month)}-01`,
        to_date: `${year}-${pad(month)}-${pad(lastDay)}`,
    };
}

export function lastMonthDateRange(referenceDate = new Date()) {
    let [year, month] = toIsoDate(referenceDate).split('-').map(Number);
    month -= 1;

    if (month < 1) {
        month = 12;
        year -= 1;
    }

    const lastDay = new Date(year, month, 0).getDate();

    return {
        from_date: `${year}-${pad(month)}-01`,
        to_date: `${year}-${pad(month)}-${pad(lastDay)}`,
    };
}

export function resolveDateRangePreset(presetKey, referenceDate = new Date()) {
    switch (presetKey) {
        case 'yesterday':
            return yesterdayDateRange(referenceDate);
        case 'this_month':
            return thisMonthDateRange(referenceDate);
        case 'last_month':
            return lastMonthDateRange(referenceDate);
        case 'today':
        default:
            return todayDateRange(referenceDate);
    }
}

export function detectDateRangePreset(fromDate, toDate, referenceDate = new Date()) {
    for (const preset of DATE_RANGE_PRESETS) {
        if (preset.key === 'custom') {
            continue;
        }

        const range = resolveDateRangePreset(preset.key, referenceDate);

        if (range.from_date === fromDate && range.to_date === toDate) {
            return preset.key;
        }
    }

    return 'custom';
}

export function formatDisplayDate(dateInput) {
    if (!dateInput) {
        return '';
    }

    const [year, month, day] = dateInput.split('-');

    if (!year || !month || !day) {
        return dateInput;
    }

    return `${day}/${month}/${year}`;
}

export function formatDateRangeLabel(fromDate, toDate) {
    if (!fromDate || !toDate) {
        return 'Select date range';
    }

    if (fromDate === toDate) {
        return formatDisplayDate(fromDate);
    }

    return `${formatDisplayDate(fromDate)} - ${formatDisplayDate(toDate)}`;
}

export function validateDateRange(fromDate, toDate) {
    if (!fromDate || !toDate) {
        return 'Select both From and To dates.';
    }

    if (toDate < fromDate) {
        return 'To Date cannot be earlier than From Date.';
    }

    return '';
}
