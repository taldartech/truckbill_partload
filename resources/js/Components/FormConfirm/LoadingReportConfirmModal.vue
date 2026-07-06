<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Loading Report"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="confirm-section-title">Report Details</div>
        <div class="row g-2 mb-3">
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
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Truck</span>
                    <span class="confirm-field-value">{{ lookupLabel(trucks, form.truck_id) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Driver</span>
                    <span class="confirm-field-value">{{ lookupLabel(drivers, form.driver_id) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Bilties</span>
                    <span class="confirm-field-value">{{ activeRows.length }}</span>
                </div>
            </div>
        </div>

        <div class="confirm-section-title">Totals</div>
        <div class="row g-2">
            <div class="col-6">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Packages</span>
                    <span class="confirm-field-value">{{ totals.totalPackages }}</span>
                </div>
            </div>
            <div class="col-6">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Weight</span>
                    <span class="confirm-field-value">{{ totals.totalWeight }}</span>
                </div>
            </div>
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
    drivers: { type: Array, default: () => [] },
    totals: {
        type: Object,
        default: () => ({
            totalPackages: 0,
            totalWeight: 0,
        }),
    },
});

defineEmits(['cancel', 'confirm', 'confirm-print']);

const activeRows = computed(() =>
    (props.form.details || []).filter((row) => row.bilty_id),
);
</script>
