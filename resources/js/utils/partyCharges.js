export const CHARGE_TYPE_FIXED = 'fixed';
export const CHARGE_TYPE_PERCENTAGE = 'percentage';

export const PARTY_CHARGE_FIELDS = [
    { amount: 'bilty_charge', type: 'bilty_charge_type', label: 'Bilty Charge' },
    { amount: 'surcharge', type: 'surcharge_type', label: 'Surcharge' },
    { amount: 'handling_charge', type: 'handling_charge_type', label: 'Handling Charge' },
];

export const emptyPartyCharges = () => PARTY_CHARGE_FIELDS.reduce((charges, field) => {
    charges[field.amount] = '';
    charges[field.type] = CHARGE_TYPE_FIXED;
    return charges;
}, {});

export const partyChargesFrom = (party = {}) => PARTY_CHARGE_FIELDS.reduce((charges, field) => {
    charges[field.amount] = party?.[field.amount] ?? '';
    charges[field.type] = party?.[field.type] || CHARGE_TYPE_FIXED;
    return charges;
}, {});

export const isPercentageCharge = (party, field) => party?.[field.type] === CHARGE_TYPE_PERCENTAGE;

/**
 * Party default for a bilty charge; null when the party has no value set.
 */
export const resolvePartyCharge = (party, field, freight) => {
    const value = parseFloat(party?.[field.amount]);
    if (!Number.isFinite(value) || value < 0) {
        return null;
    }

    if (isPercentageCharge(party, field)) {
        const base = parseFloat(freight || 0) || 0;
        return Number(((base * value) / 100).toFixed(2));
    }

    return Number(value.toFixed(2));
};

export const formatPartyCharge = (amount, type) => {
    const value = parseFloat(amount);
    if (!Number.isFinite(value)) {
        return '';
    }

    if (type === CHARGE_TYPE_PERCENTAGE) {
        return `${value}% of Freight`;
    }

    return `₹${value.toFixed(2)}`;
};
