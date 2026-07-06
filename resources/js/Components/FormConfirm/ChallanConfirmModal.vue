<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Challan"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="confirm-section-title">Challan Details</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Challan No.</span>
                    <span class="confirm-field-value">{{ form.challan_number || '—' }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Date</span>
                    <span class="confirm-field-value">{{ formatConfirmDate(form.date) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">From</span>
                    <span class="confirm-field-value">{{ lookupLabel(stations, form.from_station_id) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">To</span>
                    <span class="confirm-field-value">{{ lookupLabel(stations, form.to_station_id) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Truck</span>
                    <span class="confirm-field-value">{{ lookupLabel(trucks, form.truck_id) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Driver</span>
                    <span class="confirm-field-value">{{ lookupLabel(drivers, form.driver_id) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Rows</span>
                    <span class="confirm-field-value">{{ activeRows.length }}</span>
                </div>
            </div>
        </div>

        <div class="confirm-section-title">Totals</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Nugs</span>
                    <span class="confirm-field-value">{{ totals.totalPackages }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Weight</span>
                    <span class="confirm-field-value">{{ totals.totalWeight }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">To Pay</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(totals.toPayAmount) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Crossing Freight</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(totals.crossingFreight) }}</span>
                </div>
            </div>
        </div>

        <div class="confirm-total-box">
            <span class="confirm-total-label">Challan Total</span>
            <span class="confirm-total-value">₹ {{ formatConfirmMoney(form.total_amount) }}</span>
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
    drivers: { type: Array, default: () => [] },
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

const activeRows = computed(() =>
    (props.form.details || []).filter((row) => row.bilty_id || row.inward_register_detail_id),
);
</script>
