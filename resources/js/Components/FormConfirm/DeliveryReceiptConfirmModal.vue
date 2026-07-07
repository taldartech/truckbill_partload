<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Delivery Receipt"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="confirm-section-title">Receipt Details</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Receipt No.</span>
                    <span class="confirm-field-value">{{ form.delivery_receipt_no || '—' }}</span>
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
                    <span class="confirm-field-label">GR No.</span>
                    <span class="confirm-field-value">{{ form.gr_no_bilty_no || '—' }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Payment Mode</span>
                    <span class="confirm-field-value">{{ form.mode_of_payment || '—' }}</span>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Consignee</span>
                    <span class="confirm-field-value">{{ lookupLabel(parties, form.consignee_id) }}</span>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Sub Total</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(form.total_amount) }}</span>
                </div>
            </div>
        </div>

        <div class="confirm-total-box">
            <span class="confirm-total-label">Final Amount</span>
            <span class="confirm-total-value">₹ {{ formatConfirmMoney(form.final_amount) }}</span>
        </div>
    </FormConfirmModal>
</template>

<script setup>
import FormConfirmModal from '@/Components/FormConfirm/FormConfirmModal.vue';
import { formatConfirmDate, formatConfirmMoney, lookupLabel } from '@/utils/formConfirm';

defineProps({
    show: { type: Boolean, default: false },
    showPrintButton: { type: Boolean, default: true },
    form: { type: Object, required: true },
    parties: { type: Array, default: () => [] },
});

defineEmits(['cancel', 'confirm', 'confirm-print']);
</script>
