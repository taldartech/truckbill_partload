<template>
    <FormConfirmModal :show="show" title="Confirm Bilty" @cancel="$emit('cancel')" @confirm="$emit('confirm')">
        <div class="bilty-confirm">
            <div class="bilty-kv-grid mb-2">
                <div class="bilty-kv"><span class="k">Bilty No.</span><span class="v fw-bold">{{ form.bilty_number || '—' }}</span></div>
                <div class="bilty-kv"><span class="k">Date</span><span class="v">{{ formatConfirmDate(form.date) }}</span></div>
                <div class="bilty-kv"><span class="k">Payment</span><span class="v">{{ form.payment_type || '—' }}</span></div>
                <div class="bilty-kv"><span class="k">From</span><span class="v">{{ lookupLabel(stations, form.from_station_id) }}</span></div>
                <div class="bilty-kv"><span class="k">To</span><span class="v">{{ lookupLabel(stations, form.to_station_id) }}</span></div>
                <div class="bilty-kv"><span class="k">GST Liability</span><span class="v">{{ form.gst_liability_by || '—' }}</span></div>
                <div class="bilty-kv"><span class="k">Consignor</span><span class="v">{{ lookupLabel(parties, form.consignor_id) }}</span></div>
                <div class="bilty-kv"><span class="k">Cnr GST</span><span class="v">{{ partyGst(form.consignor_id) }}</span></div>
                <div class="bilty-kv"><span class="k">Consignee</span><span class="v">{{ lookupLabel(parties, form.consignee_id) }}</span></div>
                <div class="bilty-kv"><span class="k">Cne GST</span><span class="v">{{ partyGst(form.consignee_id) }}</span></div>
                <div class="bilty-kv"><span class="k">Weight Actual</span><span class="v">{{ displayValue(form.weight_actual) }}</span></div>
                <div class="bilty-kv"><span class="k">Declared Value</span><span class="v">{{ moneyOrDash(form.declared_value) }}</span></div>
                <div class="bilty-kv"><span class="k">Invoice No.</span><span class="v">{{ form.invoice_number || '—' }}</span></div>
                <div class="bilty-kv"><span class="k">Private Marka</span><span class="v">{{ form.private_marka || '—' }}</span></div>
                <div class="bilty-kv"><span class="k">Delivery At</span><span class="v">{{ form.delivery_at || '—' }}</span></div>
                <div v-if="form.cartage_type" class="bilty-kv"><span class="k">Cartage Type</span><span class="v">{{ form.cartage_type }}</span></div>
                <div v-if="form.vendor_id" class="bilty-kv"><span class="k">Vendor</span><span class="v">{{ lookupLabel(vendors, form.vendor_id) }}</span></div>
            </div>

            <div v-if="ewaySummary" class="bilty-eway mb-2">
                <span class="k">E-Way Bill{{ form.eway_bills?.length > 1 ? 's' : '' }}:</span>
                <span class="v">{{ ewaySummary }}</span>
            </div>

            <div class="row g-2">
                <div class="col-lg-7">
                    <div class="bilty-confirm-label">Items</div>
                    <div class="table-responsive bilty-confirm-table-wrap">
                        <table class="table table-sm table-bordered mb-0 bilty-confirm-table">
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
                                <tr v-for="(row, index) in form.details" :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ lookupLabel(goods, row.good_id) }}</td>
                                    <td>{{ lookupLabel(packageTypes, row.package_type_id) }}</td>
                                    <td class="text-end">{{ displayValue(row.no_of_packages) }}</td>
                                    <td class="text-end">{{ displayValue(row.gross_weight) }}</td>
                                    <td class="text-end">{{ displayValue(row.charged_weight) }}</td>
                                    <td>{{ row.rate_type || '—' }}</td>
                                    <td class="text-end">{{ displayValue(row.rate) }}</td>
                                    <td class="text-end">{{ formatConfirmMoney(row.amount) }}</td>
                                </tr>
                                <tr v-if="!form.details?.length">
                                    <td colspan="9" class="text-center text-muted py-1">No items</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="bilty-confirm-label">Charges</div>
                    <div class="bilty-confirm-charges">
                        <div v-if="chargeLines.length === 0" class="bilty-charge-row text-muted">No charges</div>
                        <div v-for="line in chargeLines" :key="line.label" class="bilty-charge-row">
                            <span>{{ line.label }}</span>
                            <span :class="line.amount < 0 ? 'text-danger' : ''">
                                {{ line.amount < 0 ? '−' : '' }}₹ {{ formatConfirmMoney(Math.abs(line.amount)) }}
                            </span>
                        </div>
                        <div class="bilty-charge-total">
                            <span>Total Amount</span>
                            <span>₹ {{ formatConfirmMoney(form.amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import { computed } from 'vue';
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
});

defineEmits(['cancel', 'confirm']);

const partyGst = (partyId) => {
    const party = props.parties.find((item) => String(item.id) === String(partyId));
    return party?.gst_number || '—';
};

const displayValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
};

const moneyOrDash = (value) => {
    const amount = parseFloat(value || 0);
    if (!value && amount === 0) {
        return '—';
    }

    return `₹ ${formatConfirmMoney(amount)}`;
};

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

const chargeLines = computed(() => {
    const lines = [];

    const addLine = (label, value) => {
        const amount = parseFloat(value || 0);
        if (amount !== 0) {
            lines.push({ label, amount });
        }
    };

    addLine('Total Freight', props.form.freight);
    addLine('Bilty Charge', props.form.bilty_charge);
    addLine('Surcharge', props.form.surcharge);
    addLine('Handling Charge', props.form.handling_charge);
    addLine('Risk / DD Charge', props.form.risk_dd_charge);

    const cartage = parseFloat(props.form.cartage || 0);
    if (cartage !== 0) {
        let cartageLabel = 'Cartage';
        if (props.form.cartage_type === 'Due') {
            cartageLabel = 'Cartage (Due)';
        } else if (props.form.cartage_type === 'Paid') {
            cartageLabel = 'Cartage (Paid)';
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

    const lessAdvance = parseFloat(props.form.less_advance_received || 0);
    if (lessAdvance !== 0) {
        lines.push({ label: 'Less Advance', amount: -Math.abs(lessAdvance) });
    }

    const chargeSubtotal = lines.reduce((sum, line) => sum + line.amount, 0);
    const gstRate = props.form.gst_liability_by === 'Exempted' ? 0 : parseFloat(props.form.gst || 0);
    const gstAmount = chargeSubtotal * (gstRate / 100);
    if (gstAmount !== 0) {
        lines.push({ label: `GST (${gstRate}%)`, amount: gstAmount });
    }

    return lines;
});
</script>

<style scoped>
.bilty-kv-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.2rem 0.75rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid #e2e8f0;
}

.bilty-kv {
    display: flex;
    gap: 0.35rem;
    min-width: 0;
}

.bilty-kv .k {
    color: #64748b;
    white-space: nowrap;
    flex-shrink: 0;
}

.bilty-kv .k::after {
    content: ':';
}

.bilty-kv .v {
    color: #0f172a;
    word-break: break-word;
}

.bilty-eway {
    padding: 0.25rem 0.35rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.2rem;
}

.bilty-eway .k {
    color: #64748b;
    font-weight: 600;
    margin-right: 0.25rem;
}

.bilty-confirm-label {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #27ae60;
    margin-bottom: 0.25rem;
}

.bilty-confirm-table-wrap {
    max-height: 160px;
    overflow-y: auto;
}

.bilty-confirm-table th,
.bilty-confirm-table td {
    padding: 0.15rem 0.3rem;
    vertical-align: middle;
}

.bilty-confirm-table thead th {
    background: #f8fafc;
    font-weight: 600;
    white-space: nowrap;
}

.bilty-confirm-charges {
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    overflow: hidden;
}

.bilty-charge-row {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.18rem 0.4rem;
    border-bottom: 1px solid #f1f5f9;
}

.bilty-charge-row span:first-child {
    color: #64748b;
}

.bilty-charge-row span:last-child {
    font-weight: 600;
    white-space: nowrap;
}

.bilty-charge-total {
    display: flex;
    justify-content: space-between;
    padding: 0.3rem 0.4rem;
    background: rgba(39, 174, 96, 0.08);
    font-weight: 700;
    color: #27ae60;
}

:deep(.form-confirm-card) {
    width: min(900px, 96vw);
}

:deep(.form-confirm-body) {
    padding: 0.65rem 0.75rem;
}

@media (max-width: 768px) {
    .bilty-kv-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
