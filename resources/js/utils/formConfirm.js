export function lookupLabel(options, id, labelKeys = ['name', 'title', 'label', 'truck_number', 'bilty_number', 'gr_no', 'challan_number']) {
    if (!id) {
        return '—';
    }

    const item = (options || []).find((option) => String(option.id) === String(id));
    if (!item) {
        return '—';
    }

    for (const key of labelKeys) {
        if (item[key]) {
            return item[key];
        }
    }

    return '—';
}

export function formatConfirmDate(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('en-GB');
}

export function formatConfirmMoney(value) {
    const amount = parseFloat(value || 0);

    return amount.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
