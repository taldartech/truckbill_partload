<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Challan"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Save Challan"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="ccm">
            <section class="ccm__section">
                <div class="ccm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Challan details</span>
                </div>
                <div class="ccm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="ccm__meta-item"
                    >
                        <span class="ccm__meta-label">{{ field.label }}</span>
                        <span
                            class="ccm__meta-value"
                            :class="{ 'ccm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="ccm__section">
                <div class="ccm__section-head">
                    <i class="fas fa-list"></i>
                    <span>Loading items ({{ activeRows.length }})</span>
                </div>
                <div class="ccm__table-wrap">
                    <table class="table table-sm mb-0 ccm__table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>GR/Bilty</th>
                                <th>Station</th>
                                <th class="text-end">Nugs</th>
                                <th class="text-end">Weight</th>
                                <th>Goods</th>
                                <th class="text-end">Freight</th>
                                <th>B.O.B.</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in activeRows" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ rowLabel(row) }}</td>
                                <td>{{ row.to_station_name || '—' }}</td>
                                <td class="text-end">{{ displayValue(row.nugs) }}</td>
                                <td class="text-end">{{ displayValue(row.weight) }}</td>
                                <td>{{ row.goods_name || '—' }}</td>
                                <td class="text-end">
                                    <span class="ccm__amount">₹{{ formatConfirmMoney(row.freight) }}</span>
                                </td>
                                <td>{{ row.bob || '—' }}</td>
                                <td>{{ row.row_type === 'bilty' ? 'Booking' : 'Crossing' }}</td>
                            </tr>
                            <tr v-if="activeRows.length === 0">
                                <td colspan="9" class="text-center text-muted py-1">No items</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="extraChargeLines.length > 0" class="ccm__charge-chips">
                <div
                    v-for="line in extraChargeLines"
                    :key="line.label"
                    class="ccm__charge-chip"
                >
                    <span class="ccm__total-label">{{ line.label }}</span>
                    <strong :class="line.amount < 0 ? 'text-danger' : ''">
                        {{ line.amount < 0 ? '−' : '' }}₹{{ formatConfirmMoney(Math.abs(line.amount)) }}
                    </strong>
                </div>
            </div>

            <section class="ccm__totals">
                <div class="ccm__total">
                    <span class="ccm__total-label">Nugs</span>
                    <strong>{{ totals.totalPackages || '—' }}</strong>
                </div>
                <div class="ccm__total">
                    <span class="ccm__total-label">Weight</span>
                    <strong>{{ formatWeight(totals.totalWeight) }}</strong>
                </div>
                <div class="ccm__total">
                    <span class="ccm__total-label">To Pay</span>
                    <strong class="ccm__amount">₹{{ formatConfirmMoney(totals.toPayAmount) }}</strong>
                </div>
                <div class="ccm__total">
                    <span class="ccm__total-label">Crossing</span>
                    <strong class="ccm__amount">₹{{ formatConfirmMoney(totals.crossingFreight) }}</strong>
                </div>
                <div class="ccm__total ccm__total--profit">
                    <span class="ccm__total-label">Total Amount</span>
                    <strong class="text-success">₹{{ formatConfirmMoney(form.total_amount) }}</strong>
                </div>
            </section>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import { computed } from 'vue';
import FormConfirmModal from '@/Components/FormConfirm/FormConfirmModal.vue';
import { formatConfirmDate, formatConfirmMoney, lookupLabel } from '@/utils/formConfirm';

const props = defineProps({
    show: { type: Boolean, default: false },
    showPrintButton: { type: Boolean, default: true },
    form: { type: Object, required: true },
    stations: { type: Array, default: () => [] },
    trucks: { type: Array, default: () => [] },
    totals: {
        type: Object,
        default: () => ({
            totalPackages: 0,
            totalWeight: 0,
            toPayAmount: 0,
            crossingFreight: 0,
        }),
    },
});

defineEmits(['cancel', 'confirm', 'confirm-print']);

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

    pushField(fields, 'Challan Number', props.form.challan_number || '—', { always: true, accent: true });
    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'From', lookupLabel(props.stations, props.form.from_station_id), { always: true });
    pushField(fields, 'To', lookupLabel(props.stations, props.form.to_station_id), { always: true });
    pushField(fields, 'Truck', lookupLabel(props.trucks, props.form.truck_id), { always: true, accent: true });
    pushField(fields, 'Driver', props.form.driver_name || '—', { always: true });
    pushField(fields, 'Balance', props.form.total_balance_type);
    pushField(fields, 'Remark', props.form.remark);

    return fields;
});

const activeRows = computed(() =>
    (props.form.details || []).filter((row) => row.bilty_id || row.inward_register_detail_id),
);

const rowLabel = (row) => {
    if (row.row_type === 'bilty') {
        return row.bilty_number || '—';
    }

    return row.gr_no || '—';
};

const formatWeight = (value) => {
    const amount = parseFloat(value || 0);

    if (!amount) {
        return '—';
    }

    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2);
};

const extraChargeLines = computed(() => {
    const lines = [];

    const addLine = (label, value) => {
        const amount = parseFloat(value || 0);
        if (amount !== 0) {
            lines.push({ label, amount });
        }
    };

    addLine('Labour', props.form.labour_charge);
    addLine('Kat', props.form.kat);
    addLine('Home Delivery', props.form.home_delivery_charge);
    addLine('Truck Freight', props.form.truck_freight);
    addLine('Cash Receive', props.form.cash_received);
    addLine('Cash Paid', props.form.cash_payment);
    addLine('Paid / Adv.', props.form.paid_amount);
    addLine('Cartage / Dalali', props.form.cartage_dalali_pf);
    addLine('GST', props.form.gst);

    return lines;
});
</script>

<style scoped>
.ccm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.ccm__section-head {
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

.ccm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.ccm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.ccm__meta-item:nth-child(6n) {
    border-right: 0;
}

.ccm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.ccm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.ccm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.ccm__table-wrap {
    overflow-x: auto;
}

.ccm__table {
    --bs-table-bg: transparent;
}

.ccm__table thead th {
    white-space: nowrap;
    background: #f8fafc;
    font-size: 0.62rem;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.28rem 0.4rem;
}

.ccm__table td {
    font-size: 0.72rem;
    vertical-align: middle;
    padding: 0.28rem 0.4rem;
    white-space: nowrap;
}

.ccm__amount {
    display: inline-block;
    font-weight: 700;
    color: var(--brand-accent-blue, #2d8cff);
    background: rgba(45, 140, 255, 0.1);
    border-radius: 0.25rem;
    padding: 0.05rem 0.28rem;
}

.ccm__charge-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.45rem;
}

.ccm__charge-chip {
    min-width: 6.5rem;
    text-align: center;
    padding: 0.28rem 0.4rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.ccm__totals {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.3rem;
}

.ccm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.ccm__total--profit {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.ccm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.ccm__total strong,
.ccm__charge-chip strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .ccm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ccm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .ccm__meta-item:nth-child(4n) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .ccm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ccm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .ccm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .ccm__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
