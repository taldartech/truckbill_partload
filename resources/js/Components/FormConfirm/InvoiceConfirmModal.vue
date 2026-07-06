<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Tax Invoice"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="confirm-section-title">Invoice Details</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Invoice No.</span>
                    <span class="confirm-field-value">{{ form.invoice_no || '—' }}</span>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Date</span>
                    <span class="confirm-field-value">{{ formatConfirmDate(form.date) }}</span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Party</span>
                    <span class="confirm-field-value">{{ lookupLabel(parties, form.party_id) }}</span>
                </div>
            </div>
        </div>

        <div class="confirm-section-title">Invoice Breakdown</div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Selected Bilties</span>
                    <span class="confirm-field-value">{{ breakdown.count }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Total Amount</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(breakdown.total_amount) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">GST Amount</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(breakdown.gst_amount) }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="confirm-field-box">
                    <span class="confirm-field-label">Net Amount</span>
                    <span class="confirm-field-value">₹ {{ formatConfirmMoney(breakdown.net_amount) }}</span>
                </div>
            </div>
        </div>

        <div class="confirm-total-box">
            <span class="confirm-total-label">Net Payable</span>
            <span class="confirm-total-value">₹ {{ formatConfirmMoney(breakdown.net_amount) }}</span>
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
</script>
