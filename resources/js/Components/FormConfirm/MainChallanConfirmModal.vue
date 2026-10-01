<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Main Challan"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Save Main Challan"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="mccm">
            <section class="mccm__section">
                <div class="mccm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Main challan details</span>
                </div>
                <div class="mccm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="mccm__meta-item"
                    >
                        <span class="mccm__meta-label">{{ field.label }}</span>
                        <span
                            class="mccm__meta-value"
                            :class="{ 'mccm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="mccm__section">
                <div class="mccm__section-head">
                    <i class="fas fa-list"></i>
                    <span>Linked challans ({{ activeRows.length }})</span>
                </div>
                <div class="mccm__table-wrap">
                    <table class="table table-sm mb-0 mccm__table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Challan No.</th>
                                <th>Station</th>
                                <th class="text-end">Nugs</th>
                                <th class="text-end">Weight</th>
                                <th class="text-end">To Pay</th>
                                <th class="text-end">Crossing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in activeRows" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ row.challan_number || '—' }}</td>
                                <td>{{ row.to_station_name || '—' }}</td>
                                <td class="text-end">{{ displayValue(row.nugs) }}</td>
                                <td class="text-end">{{ displayValue(row.weight) }}</td>
                                <td class="text-end">
                                    <span class="mccm__amount">₹{{ formatConfirmMoney(row.to_pay) }}</span>
                                </td>
                                <td class="text-end">₹{{ formatConfirmMoney(row.crossing) }}</td>
                            </tr>
                            <tr v-if="activeRows.length === 0">
                                <td colspan="7" class="text-center text-muted py-1">No challans</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mccm__totals">
                <div class="mccm__total">
                    <span class="mccm__total-label">Nugs</span>
                    <strong>{{ totals.totalPackages || '—' }}</strong>
                </div>
                <div class="mccm__total">
                    <span class="mccm__total-label">Weight</span>
                    <strong>{{ formatWeight(totals.totalWeight) }}</strong>
                </div>
                <div class="mccm__total">
                    <span class="mccm__total-label">To Pay</span>
                    <strong class="mccm__amount">₹{{ formatConfirmMoney(totals.toPayAmount) }}</strong>
                </div>
                <div class="mccm__total">
                    <span class="mccm__total-label">Crossing</span>
                    <strong class="mccm__amount">₹{{ formatConfirmMoney(totals.crossingFreight) }}</strong>
                </div>
                <div class="mccm__total mccm__total--profit">
                    <span class="mccm__total-label">Total Amount</span>
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
    agents: { type: Array, default: () => [] },
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

    pushField(fields, 'Main Challan No.', props.form.main_challan_number || '—', { always: true, accent: true });
    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'From', lookupLabel(props.stations, props.form.from_station_id), { always: true });
    pushField(fields, 'To', lookupLabel(props.stations, props.form.to_station_id), { always: true });
    pushField(fields, 'Truck', lookupLabel(props.trucks, props.form.truck_id), { always: true, accent: true });
    pushField(fields, 'Driver', props.form.driver_name || '—', { always: true });
    pushField(fields, 'To (Account)', props.form.pay_by_agent_id ? lookupLabel(props.agents, props.form.pay_by_agent_id) : '');
    pushField(fields, 'Balance', props.form.total_balance_type);
    pushField(fields, 'Remark', props.form.remark);

    return fields;
});

const activeRows = computed(() =>
    (props.form.details || []).filter((row) => row.challan_id),
);

const formatWeight = (value) => {
    const amount = parseFloat(value || 0);

    if (!amount) {
        return '—';
    }

    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2);
};
</script>

<style scoped>
.mccm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.mccm__section-head {
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

.mccm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.mccm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.mccm__meta-item:nth-child(6n) {
    border-right: 0;
}

.mccm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.mccm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.mccm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.mccm__table-wrap {
    overflow-x: auto;
}

.mccm__table {
    --bs-table-bg: transparent;
}

.mccm__table thead th {
    white-space: nowrap;
    background: #f8fafc;
    font-size: 0.62rem;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.28rem 0.4rem;
}

.mccm__table td {
    font-size: 0.72rem;
    vertical-align: middle;
    padding: 0.28rem 0.4rem;
    white-space: nowrap;
}

.mccm__amount {
    display: inline-block;
    font-weight: 700;
    color: var(--brand-accent-blue, #2d8cff);
    background: rgba(45, 140, 255, 0.1);
    border-radius: 0.25rem;
    padding: 0.05rem 0.28rem;
}

.mccm__totals {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.3rem;
}

.mccm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.mccm__total--profit {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.mccm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.mccm__total strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .mccm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .mccm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .mccm__meta-item:nth-child(4n) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .mccm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mccm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .mccm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .mccm__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
