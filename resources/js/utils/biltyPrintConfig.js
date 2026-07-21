export const BOB_KEYS = ['to_pay', 'paid', 'tbb', 'foc'];

export const BOB_LABELS = {
    to_pay: 'To Pay',
    paid: 'Paid',
    tbb: 'TBB',
    foc: 'FOC',
};

export const biltyPrintCopies = [
    { key: 'consignor', label: 'Consignor Copy' },
    { key: 'consignee', label: 'Consignee Copy' },
    { key: 'driver', label: 'Driver Copy' },
    { key: 'office', label: 'Office Copy' },
];

const defaultShowAmountByBob = (showAmount) => {
    if (!showAmount) {
        return Object.fromEntries(BOB_KEYS.map((key) => [key, false]));
    }

    return {
        to_pay: true,
        paid: true,
        tbb: false,
        foc: true,
    };
};

export const defaultBiltyPrintConfig = () => ({
    note: '',
    copies: Object.fromEntries(
        biltyPrintCopies.map(({ key }) => [
            key,
            {
                enabled: key === 'consignor' || key === 'consignee' || key === 'driver' || key === 'office',
                show_amount_by_bob: defaultShowAmountByBob(key === 'consignor' || key === 'office'),
            },
        ]),
    ),
});

const resolveShowAmountByBob = (storedCopy, defaultCopy) => {
    if (storedCopy?.show_amount_by_bob) {
        return Object.fromEntries(
            BOB_KEYS.map((key) => [key, !!storedCopy.show_amount_by_bob[key]]),
        );
    }

    if (storedCopy && Object.prototype.hasOwnProperty.call(storedCopy, 'show_amount')) {
        return defaultShowAmountByBob(!!storedCopy.show_amount);
    }

    return defaultCopy.show_amount_by_bob;
};

export const resolveBiltyPrintConfig = (stored) => {
    const defaults = defaultBiltyPrintConfig();

    if (!stored) {
        return defaults;
    }

    let note = stored.note ?? '';

    if (!note) {
        for (const { key } of biltyPrintCopies) {
            const legacyNote = stored.copies?.[key]?.note;
            if (legacyNote) {
                note = legacyNote;
                break;
            }
        }
    }

    return {
        note,
        copies: Object.fromEntries(
            biltyPrintCopies.map(({ key }) => [
                key,
                {
                    enabled: stored.copies?.[key]?.enabled ?? defaults.copies[key].enabled,
                    show_amount_by_bob: resolveShowAmountByBob(
                        stored.copies?.[key],
                        defaults.copies[key],
                    ),
                },
            ]),
        ),
    };
};

export const normalizeBiltyPrintConfigPayload = (config) => ({
    note: config?.note ?? '',
    copies: Object.fromEntries(
        biltyPrintCopies.map(({ key }) => [
            key,
            {
                enabled: !!config?.copies?.[key]?.enabled,
                show_amount_by_bob: Object.fromEntries(
                    BOB_KEYS.map((bob) => [
                        bob,
                        !!config?.copies?.[key]?.show_amount_by_bob?.[bob],
                    ]),
                ),
            },
        ]),
    ),
});
