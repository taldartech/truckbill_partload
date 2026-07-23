<template>
    <Transition name="fade">
        <div v-if="show" class="gr-confirm-overlay" @click.self="onCancel">
            <div class="gr-confirm-card" role="alertdialog" aria-modal="true">
                <div class="gr-confirm-header">
                    <h6 class="mb-0 fw-bold">Confirm GR Selection</h6>
                </div>
                <div class="gr-confirm-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="gr-confirm-field">
                                <span class="label">GR No.</span>
                                <span class="value">{{ details?.gr_no_bilty_no || '—' }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="gr-confirm-field">
                                <span class="label">Consignee</span>
                                <span class="value">{{ details?.consignee_name || '—' }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="gr-confirm-field">
                                <span class="label">To Station</span>
                                <span class="value">{{ details?.to_station || '—' }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="gr-confirm-field">
                                <span class="label">Item</span>
                                <span class="value">{{ details?.item_name || '—' }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="gr-confirm-field">
                                <span class="label">Packages</span>
                                <span class="value">{{ formatNumber(details?.packages) }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="gr-confirm-field">
                                <span class="label">Weight</span>
                                <span class="value">{{ formatNumber(details?.weight, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="gr-confirm-field">
                                <span class="label">Freight</span>
                                <span class="value">₹ {{ formatNumber(details?.freight, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="gr-confirm-field">
                                <span class="label">Delivery Charge</span>
                                <span class="value">₹ {{ formatNumber(details?.delivery_charge, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="gr-confirm-field">
                                <span class="label">Haste</span>
                                <span class="value">{{ details?.haste || '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gr-confirm-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" @click="onCancel">
                        Cancel
                    </button>
                    <button ref="confirmBtn" type="button" class="btn btn-sm btn-primary px-3 fw-bold" @click="onConfirm">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    details: { type: Object, default: null },
});

const emit = defineEmits(['confirm', 'cancel']);

const confirmBtn = ref(null);

watch(() => props.show, async (open) => {
    if (open) {
        await nextTick();
        confirmBtn.value?.focus();
    }
});

const formatNumber = (value, decimals = 0) => {
    const num = Number(value ?? 0);

    return Number.isFinite(num) ? num.toFixed(decimals) : (0).toFixed(decimals);
};

const onConfirm = () => emit('confirm');
const onCancel = () => emit('cancel');
</script>

<style scoped>
.gr-confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    padding: 1rem;
}

.gr-confirm-card {
    width: 100%;
    max-width: 520px;
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.2);
    overflow: hidden;
}

.gr-confirm-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--brand-border, #dee2e6);
    background: #f8f9fa;
}

.gr-confirm-body {
    padding: 1rem;
}

.gr-confirm-field {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.5rem 0.65rem;
    height: 100%;
}

.gr-confirm-field .label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #6c757d;
    margin-bottom: 0.15rem;
}

.gr-confirm-field .value {
    display: block;
    font-weight: 600;
    color: #212529;
    word-break: break-word;
}

.gr-confirm-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--brand-border, #dee2e6);
    background: #f8f9fa;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
