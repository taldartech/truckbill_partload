<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Inward Register"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Save Inward Register"
        :show-print-button="false"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="ircm">
            <section class="ircm__section">
                <div class="ircm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Register details</span>
                </div>
                <div class="ircm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="ircm__meta-item"
                    >
                        <span class="ircm__meta-label">{{ field.label }}</span>
                        <span
                            class="ircm__meta-value"
                            :class="{ 'ircm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="ircm__section">
                <div class="ircm__section-head">
                    <i class="fas fa-list"></i>
                    <span>Entries ({{ activeRows.length }})</span>
                </div>
                <div class="ircm__table-wrap">
                    <table class="table table-sm mb-0 ircm__table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>G.R. No.</th>
                                <th>To</th>
                                <th>Route</th>
                                <th class="text-end">Pkg.</th>
                                <th class="text-end">Freight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in activeRows" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ row.gr_no || '—' }}</td>
                                <td>{{ stationLabel(row.to_station_id) }}</td>
                                <td>{{ dispositionLabel(row) }}</td>
                                <td class="text-end">{{ displayValue(row.no_of_packages) }}</td>
                                <td class="text-end">
                                    <span class="ircm__amount">₹{{ formatConfirmMoney(row.freight) }}</span>
                                </td>
                            </tr>
                            <tr v-if="activeRows.length === 0">
                                <td colspan="6" class="text-center text-muted py-1">No entries</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="ircm__totals">
                <div class="ircm__total">
                    <span class="ircm__total-label">Entries</span>
                    <strong>{{ activeRows.length || '—' }}</strong>
                </div>
                <div class="ircm__total">
                    <span class="ircm__total-label">Packages</span>
                    <strong>{{ totals.totalPackages || '—' }}</strong>
                </div>
                <div class="ircm__total">
                    <span class="ircm__total-label">Delivery Freight</span>
                    <strong class="ircm__amount">₹{{ formatConfirmMoney(totals.totalDeliveryFreight) }}</strong>
                </div>
                <div class="ircm__total ircm__total--profit">
                    <span class="ircm__total-label">Crossing Freight</span>
                    <strong class="text-success">₹{{ formatConfirmMoney(totals.totalCrossingFreight) }}</strong>
                </div>
            </section>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FormConfirmModal from '@/Components/FormConfirm/FormConfirmModal.vue';
import { formatConfirmDate, formatConfirmMoney, lookupLabel } from '@/utils/formConfirm';

const page = usePage();

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    stations: { type: Array, default: () => [] },
    trucks: { type: Array, default: () => [] },
    totals: {
        type: Object,
        default: () => ({
            totalPackages: 0,
            totalDeliveryFreight: 0,
            totalCrossingFreight: 0,
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

    pushField(fields, 'Inward No.', props.form.inward_register_no || '—', { always: true, accent: true });
    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'From', lookupLabel(props.stations, props.form.from_station_id), { always: true });
    pushField(fields, 'To', lookupLabel(props.stations, props.form.to_station_id), { always: true });
    pushField(fields, 'Challan No.', props.form.challan_number);
    pushField(fields, 'Challan Date', props.form.challan_date ? formatConfirmDate(props.form.challan_date) : null);
    pushField(fields, 'Truck', lookupLabel(props.trucks, props.form.truck_id), { always: true, accent: true });
    pushField(fields, 'Driver', props.form.driver_name || '—', { always: true });

    return fields;
});

const activeRows = computed(() => props.form.details || []);

const branchStationId = computed(() => page.props.auth.active_branch?.station_id ?? null);

const stationLabel = (stationId) => lookupLabel(props.stations, stationId);

const dispositionLabel = (row) => {
    if (row.disposition === 'delivery') {
        return 'Delivery';
    }

    if (row.disposition === 'forward') {
        return 'Forward';
    }

    if (!row.to_station_id || !branchStationId.value) {
        return '—';
    }

    return String(row.to_station_id) === String(branchStationId.value) ? 'Delivery' : 'Forward';
};
</script>

<style scoped>
.ircm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.ircm__section-head {
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

.ircm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.ircm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.ircm__meta-item:nth-child(6n) {
    border-right: 0;
}

.ircm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.ircm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.ircm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.ircm__table-wrap {
    overflow-x: auto;
}

.ircm__table {
    --bs-table-bg: transparent;
}

.ircm__table thead th {
    white-space: nowrap;
    background: #f8fafc;
    font-size: 0.62rem;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.28rem 0.4rem;
}

.ircm__table td {
    font-size: 0.72rem;
    vertical-align: middle;
    padding: 0.28rem 0.4rem;
    white-space: nowrap;
}

.ircm__amount {
    display: inline-block;
    font-weight: 700;
    color: var(--brand-accent-blue, #2d8cff);
    background: rgba(45, 140, 255, 0.1);
    border-radius: 0.25rem;
    padding: 0.05rem 0.28rem;
}

.ircm__totals {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.3rem;
}

.ircm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.ircm__total--profit {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.ircm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.ircm__total strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .ircm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ircm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .ircm__meta-item:nth-child(4n) {
        border-right: 0;
    }
}

@media (max-width: 767.98px) {
    .ircm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ircm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .ircm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .ircm__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
