<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Tax Invoice"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Generate Invoice"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="ivcm">
            <section class="ivcm__section">
                <div class="ivcm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Invoice details</span>
                </div>
                <div class="ivcm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="ivcm__meta-item"
                    >
                        <span class="ivcm__meta-label">{{ field.label }}</span>
                        <span
                            class="ivcm__meta-value"
                            :class="{ 'ivcm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="ivcm__section">
                <div class="ivcm__section-head">
                    <i class="fas fa-list"></i>
                    <span>Selected bilties ({{ breakdown.count }})</span>
                </div>
                <div class="ivcm__table-wrap">
                    <table class="table table-sm mb-0 ivcm__table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Goods</th>
                                <th class="text-end">Packages</th>
                                <th class="text-end">Freight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in selectedBiltyRows" :key="row.id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ row.goods || '—' }}</td>
                                <td class="text-end">{{ displayValue(row.packages) }}</td>
                                <td class="text-end">
                                    <span class="ivcm__amount">₹{{ formatConfirmMoney(row.freight) }}</span>
                                </td>
                            </tr>
                            <tr v-if="selectedBiltyRows.length === 0">
                                <td colspan="4" class="text-center text-muted py-1">No bilties selected</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="ivcm__totals">
                <div class="ivcm__total">
                    <span class="ivcm__total-label">Bilties</span>
                    <strong>{{ breakdown.count || '—' }}</strong>
                </div>
                <div class="ivcm__total">
                    <span class="ivcm__total-label">Total Amount</span>
                    <strong class="ivcm__amount">₹{{ formatConfirmMoney(breakdown.total_amount) }}</strong>
                </div>
                <div class="ivcm__total">
                    <span class="ivcm__total-label">GST Amount</span>
                    <strong class="ivcm__amount">₹{{ formatConfirmMoney(breakdown.gst_amount) }}</strong>
                </div>
                <div class="ivcm__total ivcm__total--profit">
                    <span class="ivcm__total-label">Net Amount</span>
                    <strong class="text-success">₹{{ formatConfirmMoney(breakdown.net_amount) }}</strong>
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
    parties: { type: Array, default: () => [] },
    breakdown: {
        type: Object,
        default: () => ({
            count: 0,
            total_amount: 0,
            gst_amount: 0,
            net_amount: 0,
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

    pushField(fields, 'Invoice No.', props.form.invoice_no, { always: true, accent: true });
    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'Party', lookupLabel(props.parties, props.form.party_id), { always: true });
    pushField(fields, 'Remark', props.form.remark);

    return fields;
});

const calculateDetailAmount = (detail) => {
    const rate = parseFloat(detail.rate) || 0;
    const weight = parseFloat(detail.gross_weight) || 0;
    const chargedWeight = parseFloat(detail.charged_weight) || 0;
    const packages = parseFloat(detail.no_of_packages) || 0;

    if (detail.rate_type === 'Per Actual Weight' || detail.rate_type === 'Per KG') {
        return rate * weight;
    }
    if (detail.rate_type === 'Per Charged Weight') {
        return rate * chargedWeight;
    }
    if (detail.rate_type === 'Per Package') {
        return rate * packages;
    }

    return rate;
};

const selectedBiltyRows = computed(() => {
    return (props.form.bilty_ids || []).map((biltyId) => {
        const rows = props.form.bilty_detail_freight?.[biltyId] || [];
        const goods = rows.map((row) => row.good_name).filter(Boolean).join(', ');
        const packages = rows.reduce((sum, row) => sum + (parseFloat(row.no_of_packages) || 0), 0);
        const freight = rows.reduce((sum, row) => sum + calculateDetailAmount(row), 0);

        return {
            id: biltyId,
            goods: goods || '—',
            packages: packages || null,
            freight,
        };
    });
});
</script>

<style scoped>
.ivcm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.ivcm__section-head {
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

.ivcm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.ivcm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.ivcm__meta-item:nth-child(6n) {
    border-right: 0;
}

.ivcm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.ivcm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.ivcm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.ivcm__table-wrap {
    overflow-x: auto;
}

.ivcm__table {
    --bs-table-bg: transparent;
}

.ivcm__table thead th {
    white-space: nowrap;
    background: #f8fafc;
    font-size: 0.62rem;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.28rem 0.4rem;
}

.ivcm__table td {
    font-size: 0.72rem;
    vertical-align: middle;
    padding: 0.28rem 0.4rem;
    white-space: nowrap;
}

.ivcm__amount {
    font-variant-numeric: tabular-nums;
}

.ivcm__totals {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.3rem;
}

.ivcm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.ivcm__total--profit {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.ivcm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.ivcm__total strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .ivcm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ivcm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .ivcm__meta-item:nth-child(4n) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .ivcm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ivcm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .ivcm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .ivcm__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575.98px) {
    .ivcm__totals {
        grid-template-columns: 1fr;
    }
}
</style>
