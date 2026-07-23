<template>
    <FormConfirmModal
        :show="show"
        title="Confirm Delivery Receipt"
        subtitle="Review every detail below before saving."
        variant="review"
        confirm-label="Yes, Save Delivery Receipt"
        :show-print-button="showPrintButton"
        @cancel="$emit('cancel')"
        @confirm="$emit('confirm')"
        @confirm-print="$emit('confirm-print')"
    >
        <div class="drcm">
            <section class="drcm__section">
                <div class="drcm__section-head">
                    <i class="fas fa-info-circle"></i>
                    <span>Receipt details</span>
                </div>
                <div class="drcm__meta">
                    <div
                        v-for="field in detailFields"
                        :key="field.label"
                        class="drcm__meta-item"
                    >
                        <span class="drcm__meta-label">{{ field.label }}</span>
                        <span
                            class="drcm__meta-value"
                            :class="{ 'drcm__meta-value--accent': field.accent }"
                        >
                            {{ field.value }}
                        </span>
                    </div>
                </div>
            </section>

            <section v-if="chargeLines.length > 0" class="drcm__section">
                <div class="drcm__section-head">
                    <i class="fas fa-calculator"></i>
                    <span>Charges</span>
                </div>
                <div class="drcm__charges">
                    <div
                        v-for="line in chargeLines"
                        :key="line.label"
                        class="drcm__charge"
                    >
                        <span class="drcm__charge-label">{{ line.label }}</span>
                        <strong>₹ {{ formatConfirmMoney(line.amount) }}</strong>
                    </div>
                </div>
            </section>

            <section class="drcm__totals">
                <div class="drcm__total">
                    <span class="drcm__total-label">Sub Total</span>
                    <strong>₹ {{ formatConfirmMoney(form.total_amount) }}</strong>
                </div>
                <div v-if="hasGst" class="drcm__total">
                    <span class="drcm__total-label">GST ({{ Number(form.gst_rate || 0) }}%)</span>
                    <strong>₹ {{ formatConfirmMoney(form.gst) }}</strong>
                </div>
                <div v-if="hasLess" class="drcm__total">
                    <span class="drcm__total-label">Less</span>
                    <strong class="text-danger">− ₹ {{ formatConfirmMoney(form.less) }}</strong>
                </div>
                <div class="drcm__total drcm__total--final">
                    <span class="drcm__total-label">Final Amount</span>
                    <strong class="text-success">₹ {{ formatConfirmMoney(form.final_amount) }}</strong>
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
});

defineEmits(['cancel', 'confirm', 'confirm-print']);

const gstOnLabel = computed(() => {
    if (props.form.gst_on === 'delivery_charge') {
        return 'Delivery Charge';
    }

    if (props.form.gst_on === 'total_charge') {
        return 'Total Charge';
    }

    return props.form.gst_on || '—';
});

const isFilled = (value) => {
    if (value === null || value === undefined) {
        return false;
    }

    const text = String(value).trim();

    return text !== '' && text !== '—';
};

const hasAmount = (value) => parseFloat(value || 0) !== 0;

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

    pushField(fields, 'Receipt No.', props.form.delivery_receipt_no);
    pushField(fields, 'Date', formatConfirmDate(props.form.date), { always: true });
    pushField(fields, 'GR No.', props.form.gr_no_bilty_no, { always: true, accent: true });
    pushField(fields, 'Payment Mode', props.form.mode_of_payment, { always: true });
    pushField(fields, 'Consignee', lookupLabel(props.parties, props.form.consignee_id), { always: true });

    if (props.form.mode_of_payment === 'Due') {
        pushField(fields, 'Payment Due From', lookupLabel(props.parties, props.form.payment_due_from_id), { always: true });
    }

    pushField(fields, 'Haste', props.form.haste);
    pushField(fields, 'GST On', gstOnLabel.value);
    pushField(fields, 'GST Pay By', props.form.gst_pay_by);

    return fields;
});

const chargeLines = computed(() => {
    const lines = [
        { label: 'Freight', amount: props.form.freight },
        { label: 'Handling', amount: props.form.handling_charge },
        { label: 'Delivery', amount: props.form.delivery_charge },
        { label: 'Stationery', amount: props.form.stationery_charge },
        { label: 'Demurrage', amount: props.form.demurrage },
        { label: 'Other', amount: props.form.other_charge },
        { label: 'Carriage', amount: props.form.carriage_charge },
    ];

    return lines.filter((line) => hasAmount(line.amount));
});

const hasGst = computed(() => hasAmount(props.form.gst));
const hasLess = computed(() => hasAmount(props.form.less));
</script>

<style scoped>
.drcm__section {
    margin-bottom: 0.45rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}

.drcm__section-head {
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

.drcm__meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
}

.drcm__meta-item {
    padding: 0.28rem 0.5rem;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    min-width: 0;
}

.drcm__meta-item:nth-child(6n) {
    border-right: 0;
}

.drcm__meta-label {
    display: block;
    font-size: 0.6rem;
    color: #64748b;
    margin-bottom: 0.05rem;
    line-height: 1.2;
}

.drcm__meta-value {
    font-size: 0.74rem;
    font-weight: 600;
    color: #0f172a;
    word-break: break-word;
    line-height: 1.25;
}

.drcm__meta-value--accent {
    display: inline-block;
    padding: 0.02rem 0.3rem;
    border-radius: 0.25rem;
    background: rgba(45, 140, 255, 0.1);
    color: var(--brand-accent-blue, #2d8cff);
}

.drcm__charges {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.3rem;
    padding: 0.35rem;
}

.drcm__charge {
    min-width: 0;
    text-align: center;
    padding: 0.28rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.drcm__charge-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.drcm__charge strong {
    font-size: 0.72rem;
    color: #0f172a;
    word-break: break-word;
}

.drcm__totals {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.3rem;
}

.drcm__total {
    min-width: 0;
    text-align: center;
    padding: 0.3rem 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
}

.drcm__total--final {
    background: rgba(47, 179, 111, 0.08);
    border-color: rgba(47, 179, 111, 0.25);
}

.drcm__total-label {
    display: block;
    font-size: 0.58rem;
    color: #64748b;
    margin-bottom: 0.08rem;
}

.drcm__total strong {
    font-size: 0.74rem;
    color: #0f172a;
    word-break: break-word;
}

@media (max-width: 1199.98px) {
    .drcm__meta {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .drcm__meta-item:nth-child(6n) {
        border-right: 1px solid #f1f5f9;
    }

    .drcm__meta-item:nth-child(4n) {
        border-right: 0;
    }

    .drcm__charges {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .drcm__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .drcm__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .drcm__meta-item:nth-child(4n) {
        border-right: 1px solid #f1f5f9;
    }

    .drcm__meta-item:nth-child(2n) {
        border-right: 0;
    }

    .drcm__charges {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .drcm__totals {
        grid-template-columns: 1fr;
    }
}
</style>
