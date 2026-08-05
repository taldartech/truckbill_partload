<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Bilty"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Save Bilty"
        :show-print-button="showPrintButton"
        :confirm-disabled="confirmDisabled"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="bcm">
            <div
                v-if="declaredValueBelowTotal"
                class="bcm__warning"
                role="alert"
            >
                <div>
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Declared value (₹ {{ formatConfirmMoney(declaredValue) }}) is less than total amount
                    (₹ {{ formatConfirmMoney(totalAmount) }}).
                </div>
                <div class="form-check mb-0 mt-1">
                    <input
                        id="bilty-declared-value-ack"
                        v-model="acknowledgedDeclaredValue"
                        class="form-check-input"
                        type="checkbox"
                    >
                    <label class="form-check-label" for="bilty-declared-value-ack">
                        I confirm that the declared value is correct even though it is less than the total amount.
                    </label>
                </div>
            </div>

            <section class="bcm__section">
                <div class="bcm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Bilty details</span>
                </div>
                <div class="bcm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="bcm__meta-item"
                    >
                        <span class="bcm__meta-label">{{ field.label }}</span>
                        <span
                            class="bcm__meta-value"
                            :class="{ 'bcm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
                <div v-if="ewaySummary" class="bcm__eway">
                    <span class="bcm__meta-label">E-Way Bill{{ form.eway_bills?.length > 1 ? 's' : '' }}</span>
                    <span class="bcm__meta-value">{{ ewaySummary }}</span>
                </div>
            </section>

            <section class="bcm__section">
                <div class="bcm__section-head">
                    <i class="fas fa-list"></i>
                    <span>Goods items ({{ itemRows.length }})</span>
                </div>
                <div class="bcm__table-wrap">
                    <table class="table table-sm mb-0 bcm__table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Goods</th>
                                <th>Pack</th>
                                <th class="text-end">Pkg</th>
                                <th class="text-end">Act</th>
                                <th class="text-end">Chg</th>
                                <th>Rate Type</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in itemRows" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ lookupLabel(goods, row.good_id) }}</td>
                                <td>{{ lookupLabel(packageTypes, row.package_type_id) }}</td>
                                <td class="text-end">{{ displayValue(row.no_of_packages) }}</td>
                                <td class="text-end">{{ displayValue(row.gross_weight) }}</td>
                                <td class="text-end">{{ displayValue(row.charged_weight) }}</td>
                                <td>{{ row.rate_type || '—' }}</td>
                                <td class="text-end">{{ displayValue(row.rate) }}</td>
                                <td class="text-end">
                                    <span class="bcm__amount">₹{{ formatConfirmMoney(row.amount) }}</span>
                                </td>
                            </tr>
                            <tr v-if="itemRows.length === 0">
                                <td colspan="9" class="text-center text-muted py-1">No items</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="extraChargeLines.length > 0" class="bcm__charge-chips">
                <div
                    v-for="line in extraChargeLines"
                    :key="line.label"
                    class="bcm__charge-chip"
                >
                    <span class="bcm__total-label">{{ line.label }}</span>
                    <strong :class="line.amount < 0 ? 'text-danger' : ''">
                        {{ line.amount < 0 ? '−' : '' }}₹{{ formatConfirmMoney(Math.abs(line.amount)) }}
                    </strong>
                </div>
            </div>

            <section class="bcm__totals">
                <div class="bcm__total">
                    <span class="bcm__total-label">Packages</span>
                    <strong>{{ totals.packages }}</strong>
                </div>
                <div class="bcm__total">
                    <span class="bcm__total-label">Actual Wt</span>
                    <strong>{{ totals.actualWeight }}</strong>
                </div>
                <div class="bcm__total">
                    <span class="bcm__total-label">Charged Wt</span>
                    <strong>{{ totals.chargedWeight }}</strong>
                </div>
                <div class="bcm__total">
                    <span class="bcm__total-label">Freight</span>
                    <strong class="bcm__amount">₹{{ formatConfirmMoney(form.freight) }}</strong>
                </div>
                <div class="bcm__total bcm__total--profit">
                    <span class="bcm__total-label">Total Amount</span>
                    <strong class="text-success">₹{{ formatConfirmMoney(form.amount) }}</strong>
                </div>
            </section>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FormConfirmModal from '@/Components/FormConfirm/FormConfirmModal.vue';
import { formatConfirmDate, formatConfirmMoney, lookupLabel } from '@/utils/formConfirm';

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    parties: { type: Array, default: () => [] },
    stations: { type: Array, default: () => [] },
    goods: { type: Array, default: () => [] },
    packageTypes: { type: Array, default: () => [] },
    vendors: { type: Array, default: () => [] },
    showPrintButton: { type: Boolean, default: true },
});

defineEmits(['cancel', 'confirm', 'confirm-print']);

const acknowledgedDeclaredValue = ref(false);

const declaredValue = computed(() => parseFloat(props.form.declared_value || 0));
const totalAmount = computed(() => parseFloat(props.form.amount || 0));

const declaredValueBelowTotal = computed(() => {
    if (props.form.payment_type === 'Paid') {
        return false;
    }

    return declaredValue.value < totalAmount.value;
});

const confirmDisabled = computed(() => declaredValueBelowTotal.value && !acknowledgedDeclaredValue.value);

watch(() => props.show, (open) => {
    if (open) {
        acknowledgedDeclaredValue.value = false;
    }
});

const partyGst = (partyId) => {
    const party = props.parties.find((item) => String(item.id) === String(partyId));
    return party?.gst_number || '';
};

const displayValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
};

const isFilled = (value) => {
    if (value === null || value === undefined) {
        return false;
    }

    const text = String(value).trim();
    return text !== '' && text !== '—';
};

const moneyValue = (value) => {
    if (!isFilled(value) || parseFloat(value || 0) === 0) {
        return '';
    }

    return `₹${formatConfirmMoney(value)}`;
};

const pushField = (fields, label, value, options = {}) => {
    if (options.always || isFilled(value)) {
        fields.push({
            label,
            value: isFilled(value) ? value : '—',
            accent: Boolean(options.accent),
        });
    }
};

const detailFields = computed(() => {
    const fields = [];

    pushField(fields, 'Bilty Number', props.form.bilty_number || '—', { always: true, accent: true });
    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'Payment', props.form.payment_type || '—', { always: true, accent: true });
    pushField(fields, 'From', lookupLabel(props.stations, props.form.from_station_id), { always: true });
    pushField(fields, 'To', lookupLabel(props.stations, props.form.to_station_id), { always: true });
    pushField(fields, 'GST Liability', props.form.gst_liability_by);
    pushField(fields, 'Consignor', lookupLabel(props.parties, props.form.consignor_id), { always: true });
    pushField(fields, 'Cnr GST', partyGst(props.form.consignor_id));
    pushField(fields, 'Consignee', lookupLabel(props.parties, props.form.consignee_id), { always: true });
    pushField(fields, 'Cne GST', partyGst(props.form.consignee_id));
    pushField(fields, 'Weight Actual', displayValue(props.form.weight_actual), { always: true });
    pushField(fields, 'Declared Value', moneyValue(props.form.declared_value));
    pushField(fields, 'Invoice No.', props.form.invoice_number);
    pushField(fields, 'Private Marka', props.form.private_marka);
    pushField(fields, 'Delivery At', props.form.delivery_at);
    pushField(fields, 'Cartage Type', props.form.cartage_type);
    pushField(fields, 'Vendor', lookupLabel(props.vendors, props.form.vendor_id));
    pushField(fields, 'Advance Payment Mode', props.form.advance_payment_mode);

    return fields;
});

const ewaySummary = computed(() => {
    const rows = (props.form.eway_bills || []).filter((eway) => eway.eway_bill_no);
    if (rows.length === 0) {
        return '';
    }

    return rows
        .map((eway) => {
            const validTill = eway.eway_bill_valid_till
                ? ` (${formatConfirmDate(eway.eway_bill_valid_till)})`
                : '';
            return `${eway.eway_bill_no}${validTill}`;
        })
        .join('; ');
});

const itemRows = computed(() => {
    return (props.form.details || []).filter((row) => {
        return Boolean(
            row.good_id
            || row.package_type_id
            || (row.no_of_packages !== '' && row.no_of_packages !== null && row.no_of_packages !== undefined)
            || (row.gross_weight !== '' && row.gross_weight !== null && row.gross_weight !== undefined)
            || (row.charged_weight !== '' && row.charged_weight !== null && row.charged_weight !== undefined)
            || (row.rate !== '' && row.rate !== null && row.rate !== undefined)
            || parseFloat(row.amount || 0) > 0
        );
    });
});

const totals = computed(() => {
    const packages = itemRows.value.reduce((sum, row) => sum + (parseFloat(row.no_of_packages) || 0), 0);
    const actualWeight = itemRows.value.reduce((sum, row) => sum + (parseFloat(row.gross_weight) || 0), 0);
    const chargedWeight = itemRows.value.reduce((sum, row) => sum + (parseFloat(row.charged_weight) || 0), 0);

    return {
        packages: packages || '—',
        actualWeight: actualWeight ? actualWeight.toFixed(2) : '—',
        chargedWeight: chargedWeight ? chargedWeight.toFixed(2) : '—',
    };
});

const extraChargeLines = computed(() => {
    const lines = [];

    const addLine = (label, value) => {
        const amount = parseFloat(value || 0);
        if (amount !== 0) {
            lines.push({ label, amount });
        }
    };

    addLine('Bilty Charge', props.form.bilty_charge);
    addLine('Surcharge', props.form.surcharge);
    addLine('Handling Charge', props.form.handling_charge);
    addLine('Risk / DD Charge', props.form.risk_dd_charge);

    const cartage = parseFloat(props.form.cartage || 0);
    if (cartage !== 0) {
        let cartageLabel = 'Cartage';
        if (props.form.cartage_type === 'Due') {
            cartageLabel = 'Cartage (Due)';
        } else if (props.form.cartage_type === 'Cash') {
            cartageLabel = 'Cartage (Cash)';
        }
        lines.push({ label: cartageLabel, amount: cartage });
    }

    (props.form.addon_charges || []).forEach((charge) => {
        const amount = parseFloat(charge.amount || 0);
        if (amount !== 0) {
            lines.push({
                label: charge.particulars?.trim() || 'Addon Charge',
                amount,
            });
        }
    });

    const chargeBase = [
        props.form.freight,
        props.form.bilty_charge,
        props.form.surcharge,
        props.form.handling_charge,
        props.form.risk_dd_charge,
        props.form.cartage,
        ...(props.form.addon_charges || []).map((charge) => charge.amount),
    ].reduce((sum, value) => sum + (parseFloat(value || 0)), 0);

    const gstRate = props.form.gst_liability_by === 'Exempted' ? 0 : parseFloat(props.form.gst || 0);
    const gstAmount = chargeBase * (gstRate / 100);
    if (gstAmount !== 0) {
        lines.push({ label: `GST (${gstRate}%)`, amount: gstAmount });
    }

    const lessAdvance = parseFloat(props.form.less_advance_received || 0);
    if (lessAdvance !== 0) {
        const mode = props.form.advance_payment_mode
            ? ` (${props.form.advance_payment_mode})`
            : '';
        lines.push({ label: `Less Advance${mode}`, amount: -Math.abs(lessAdvance) });
    }

    return lines;
});
</script>

<style scoped>
.bcm__warning {
    margin-bottom: 0.45rem;
    padding: 0.4rem 0.55rem;
    border-radius: 0.45rem;
    background: #fff8e6;
    border: 1px solid #ffe08a;
    color: #92400e;
    font-size: 0.72rem;
}

.bcm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.bcm__section-head {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.55rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.7rem;
    font-weight: 700;
    color: #334155;
}

.bcm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.bcm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.bcm__meta-item:nth-child(6n) {
    border-right: 0;
}

.bcm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.bcm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.bcm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.bcm__eway {
    padding: 0.3rem 0.55rem 0.4rem;
    border-top: 1px solid #f1f5f9;
}

.bcm__table-wrap {
    overflow-x: auto;
}

.bcm__table {
    --bs-table-bg: transparent;
}

.bcm__table thead th {
    white-space: nowrap;
    background: #f8fafc;
    font-size: 0.62rem;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.28rem 0.4rem;
}

.bcm__table td {
    font-size: 0.72rem;
    vertical-align: middle;
    padding: 0.28rem 0.4rem;
    white-space: nowrap;
}

.bcm__amount {
    display: inline-block;
    font-weight: 700;
    color: var(--brand-accent-blue, #2d8cff);
    background: rgba(45, 140, 255, 0.1);
    border-radius: 0.25rem;
    padding: 0.05rem 0.28rem;
}

.bcm__charge-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.45rem;
}

.bcm__charge-chip {
    min-width: 6.5rem;
    text-align: center;
    padding: 0.28rem 0.4rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.bcm__totals {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.3rem;
}

.bcm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.bcm__total--profit {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.bcm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.bcm__total strong,
.bcm__charge-chip strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .bcm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .bcm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .bcm__meta-item:nth-child(4n) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .bcm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bcm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .bcm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .bcm__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
