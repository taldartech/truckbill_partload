<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Loading Report"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Save Loading Report"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="lrcm">
            <section class="lrcm__section">
                <div class="lrcm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Report details</span>
                </div>
                <div class="lrcm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="lrcm__meta-item"
                    >
                        <span class="lrcm__meta-label">{{ field.label }}</span>
                        <span
                            class="lrcm__meta-value"
                            :class="{ 'lrcm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="lrcm__section">
                <div class="lrcm__section-head">
                    <i class="fas fa-list"></i>
                    <span>Loaded items ({{ activeRows.length }})</span>
                </div>
                <div class="lrcm__table-wrap">
                    <table class="table table-sm mb-0 lrcm__table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>GR/Bilty No.</th>
                                <th>Type</th>
                                <th>Consignee</th>
                                <th>Goods</th>
                                <th class="text-end">Packages</th>
                                <th class="text-end">Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in activeRows" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ row.bilty_number || row.gr_no || '—' }}</td>
                                <td>{{ row.row_type === 'bilty' ? 'Booking' : row.row_type === 'inward' ? 'Crossing' : '—' }}</td>
                                <td>{{ row.consignee_name || '—' }}</td>
                                <td>{{ row.goods_name || '—' }}</td>
                                <td class="text-end">{{ displayValue(row.no_of_packages) }}</td>
                                <td class="text-end">{{ displayValue(row.gross_weight) }}</td>
                            </tr>
                            <tr v-if="activeRows.length === 0">
                                <td colspan="7" class="text-center text-muted py-1">No items</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="lrcm__totals">
                <div class="lrcm__total">
                    <span class="lrcm__total-label">Items</span>
                    <strong>{{ activeRows.length || '—' }}</strong>
                </div>
                <div class="lrcm__total">
                    <span class="lrcm__total-label">Packages</span>
                    <strong>{{ totals.totalPackages || '—' }}</strong>
                </div>
                <div class="lrcm__total lrcm__total--profit">
                    <span class="lrcm__total-label">Total Weight</span>
                    <strong class="text-success">{{ formatWeight(totals.totalWeight) }}</strong>
                </div>
            </section>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import { computed } from 'vue';
import FormConfirmModal from '@/Components/FormConfirm/FormConfirmModal.vue';
import { formatConfirmDate, lookupLabel } from '@/utils/formConfirm';

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

    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'From', lookupLabel(props.stations, props.form.from_station_id), { always: true });
    pushField(fields, 'To', lookupLabel(props.stations, props.form.to_station_id), { always: true });
    pushField(fields, 'Truck', lookupLabel(props.trucks, props.form.truck_id), { always: true, accent: true });
    pushField(fields, 'Driver', props.form.driver_name || '—', { always: true });
    pushField(fields, 'Remark', props.form.remark);

    return fields;
});

const activeRows = computed(() =>
    (props.form.details || []).filter((row) => row.bilty_id || row.inward_register_detail_id),
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
.lrcm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.lrcm__section-head {
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

.lrcm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.lrcm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.lrcm__meta-item:nth-child(6n) {
    border-right: 0;
}

.lrcm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.lrcm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.lrcm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.lrcm__table-wrap {
    overflow-x: auto;
}

.lrcm__table {
    --bs-table-bg: transparent;
}

.lrcm__table thead th {
    white-space: nowrap;
    background: #f8fafc;
    font-size: 0.62rem;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.28rem 0.4rem;
}

.lrcm__table td {
    font-size: 0.72rem;
    vertical-align: middle;
    padding: 0.28rem 0.4rem;
    white-space: nowrap;
}

.lrcm__totals {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.3rem;
}

.lrcm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.lrcm__total--profit {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.lrcm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.lrcm__total strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .lrcm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .lrcm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .lrcm__meta-item:nth-child(4n) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .lrcm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .lrcm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .lrcm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .lrcm__totals {
        grid-template-columns: 1fr;
    }
}
</style>
