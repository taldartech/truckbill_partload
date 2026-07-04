<template>
    <FormConfirmModal :show="show" title="Confirm Inward Register" @cancel="$emit('cancel')" @confirm="$emit('confirm')">
        <div class="confirm-section-title">Register Details</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Inward No.</span>
                    <span class="confirm-field-value">{{ form.inward_register_no || '—' }}</span>
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
                    <span class="confirm-field-label">Challan No.</span>
                    <span class="confirm-field-value">{{ form.challan_number || '—' }}</span>
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
        </div>

        <div class="confirm-section-title">Entries</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Entries</span>
                    <span class="confirm-field-value">{{ form.details?.length || 0 }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Packages</span>
                    <span class="confirm-field-value">{{ totals.totalPackages }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Delivery Freight</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(totals.totalDeliveryFreight) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Crossing Freight</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(totals.totalCrossingFreight) }}</span>
                </div>
            </div>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import FormConfirmModal from '@/Components/FormConfirm/FormConfirmModal.vue';
import { formatConfirmDate, formatConfirmMoney, lookupLabel } from '@/utils/formConfirm';

defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    stations: { type: Array, default: () => [] },
    trucks: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    totals: {
        type: Object,
        default: () => ({
            totalPackages: 0,
            totalDeliveryFreight: 0,
            totalCrossingFreight: 0,
        }),
    },
});

defineEmits(['cancel', 'confirm']);
</script>
