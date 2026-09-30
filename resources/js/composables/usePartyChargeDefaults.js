import { watch } from 'vue';
import { PARTY_CHARGE_FIELDS, isPercentageCharge, resolvePartyCharge } from '@/utils/partyCharges';

/**
 * Fills Bilty Charge / Surcharge / Handling Charge from the consignor's defaults.
 * Percentage charges follow freight until the user types their own amount.
 */
export function usePartyChargeDefaults({ form, findParty, onChange }) {
    const lastApplied = {};

    const isUntouched = (field) => (
        lastApplied[field.amount] !== undefined
        && Number(form[field.amount] || 0) === Number(lastApplied[field.amount])
    );

    const applyConsignorCharges = () => {
        const party = findParty(form.consignor_id);

        PARTY_CHARGE_FIELDS.forEach((field) => {
            const charge = party ? resolvePartyCharge(party, field, form.freight) : null;

            if (charge !== null) {
                form[field.amount] = charge;
                lastApplied[field.amount] = charge;
                return;
            }

            if (isUntouched(field)) {
                form[field.amount] = 0;
            }
            delete lastApplied[field.amount];
        });

        onChange();
    };

    const refreshPercentageCharges = () => {
        const party = findParty(form.consignor_id);
        if (!party) {
            return;
        }

        let changed = false;

        PARTY_CHARGE_FIELDS.forEach((field) => {
            if (!isPercentageCharge(party, field) || !isUntouched(field)) {
                return;
            }

            const charge = resolvePartyCharge(party, field, form.freight);
            if (charge !== null && charge !== Number(form[field.amount] || 0)) {
                form[field.amount] = charge;
                lastApplied[field.amount] = charge;
                changed = true;
            }
        });

        if (changed) {
            onChange();
        }
    };

    watch(() => form.consignor_id, applyConsignorCharges);
    watch(() => form.freight, refreshPercentageCharges);

    return { applyConsignorCharges };
}
