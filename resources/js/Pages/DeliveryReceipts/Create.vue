<template>
    <AdminLayout title="Add Delivery Receipt">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Receipt Details -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Receipt Details</h2>
                                <p class="bilty-section__desc">GR reference, payment and consignee</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Receipt No.<span class="bilty-field__req">*</span></span>
                                    <input type="text" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.delivery_receipt_no }" v-model="form.delivery_receipt_no" required>
                                    <div v-if="form.errors.delivery_receipt_no" class="text-danger small mt-1">{{ form.errors.delivery_receipt_no }}</div>
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date<span class="bilty-field__req">*</span></span>
                                    <input type="date" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.date }" v-model="form.date" required>
                                    <div v-if="form.errors.date" class="invalid-feedback d-block">{{ form.errors.date }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">GR No.<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="grSourceKey"
                                    :options="grOptions"
                                    value-key="id"
                                    label-key="label"
                                    placeholder="Select GR No."
                                    @change="onGrSourceChange"
                                />
                                <div v-if="form.errors.gr_no_bilty_no" class="text-danger small mt-1">{{ form.errors.gr_no_bilty_no }}</div>
                                <div v-if="grFetchError" class="text-danger small mt-1">{{ grFetchError }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Mode of Payment<span class="bilty-field__req">*</span></label>
                                <select
                                    ref="modeOfPaymentEl"
                                    class="form-select form-select-sm"
                                    :class="{ 'is-invalid': form.errors.mode_of_payment }"
                                    v-model="form.mode_of_payment"
                                    required
                                >
                                    <option value="" disabled>Select Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank">Bank</option>
                                    <option value="Due">Due</option>
                                </select>
                                <div v-if="form.errors.mode_of_payment" class="text-danger small mt-1">{{ form.errors.mode_of_payment }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Consignee<span class="bilty-field__req">*</span></label>
                                <input
                                    type="text"
                                    class="form-control form-control-sm bg-light"
                                    :value="consigneeName"
                                    readonly
                                    tabindex="-1"
                                >
                                <div v-if="form.errors.consignee_id" class="text-danger small mt-1">{{ form.errors.consignee_id }}</div>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Haste</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.haste">
                            </div>
                            <div v-if="form.mode_of_payment === 'Due'" class="bilty-field">
                                <label class="bilty-field__label">Payment Due From<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.payment_due_from_id"
                                    :options="partyOptions"
                                    master="party"
                                    placeholder="Select Party"
                                    @created="(record) => appendMasterOption(partyOptions, record)"
                                />
                                <div v-if="form.errors.payment_due_from_id" class="text-danger small mt-1">{{ form.errors.payment_due_from_id }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Charges -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Charges</h2>
                                <p class="bilty-section__desc">Freight, handling and other charges</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="bilty-charge-list">
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Freight</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.freight" @input="calculateTotals">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Handling Charge</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.handling_charge" @input="calculateTotals">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Delivery Charge</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.delivery_charge" @input="calculateTotals">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Stationery Charge</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.stationery_charge" @input="calculateTotals">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="bilty-charge-list">
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Demurrage</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.demurrage" @input="calculateTotals">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Other Charge</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.other_charge" @input="calculateTotals">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label">Carriage Charge</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.carriage_charge" @input="calculateTotals">
                                    </div>
                                    <div class="bilty-charge-row">
                                        <label class="bilty-charge-row__label fw-bold">Sub Total Amount</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm bg-light fw-bold" v-model="form.total_amount" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. GST & Total -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">3</div>
                            <div>
                                <h2 class="bilty-section__title">GST &amp; Total</h2>
                                <p class="bilty-section__desc">Tax details and payable amount</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="bilty-form-grid bilty-form-grid--4">
                                    <div class="bilty-field">
                                        <label class="bilty-field__label">GST On</label>
                                        <select class="form-select form-select-sm" v-model="form.gst_on" @change="calculateTotals">
                                            <option value="delivery_charge">Delivery Charge (₹ {{ formatGstOnAmount(deliveryChargeGstBase) }})</option>
                                            <option value="total_charge">Total Charge (₹ {{ formatGstOnAmount(chargesSubTotal) }})</option>
                                        </select>
                                    </div>
                                    <div v-if="showGstFields" class="bilty-field">
                                        <label class="bilty-field__label">GST ({{ Number(form.gst_rate || 0) }}%)</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm bg-light" v-model="form.gst" readonly>
                                    </div>
                                    <div v-if="showGstFields" class="bilty-field">
                                        <label class="bilty-field__label">GST Pay By</label>
                                        <select class="form-select form-select-sm" v-model="form.gst_pay_by" @change="calculateTotals">
                                            <option value="Consignor">Consignor</option>
                                            <option value="Consignee">Consignee</option>
                                            <option value="Transporter">Transporter</option>
                                            <option value="Exempted">Exempted</option>
                                        </select>
                                    </div>
                                    <div class="bilty-field">
                                        <label class="bilty-field__label">Less</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.less" @input="calculateTotals">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="bilty-total-panel">
                                    <div class="bilty-total">
                                        <span class="bilty-total__label">FINAL AMOUNT</span>
                                        <input type="number" step="0.01" class="form-control form-control-sm bilty-total__input" v-model="form.final_amount" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/delivery-receipts" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button type="submit" class="btn btn-success btn-sm px-4" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>

        <DeliveryReceiptGrConfirmModal
            :show="showGrConfirm"
            :details="pendingGrDetails"
            @confirm="confirmGrSelection"
            @cancel="cancelGrSelection"
        />

        <DeliveryReceiptConfirmModal
            :show="showConfirm"
            :form="form"
            :parties="partyOptions"
            @cancel="closeConfirm"
            @confirm="confirmSave"
            @confirm-print="confirmSaveAndPrint"
        />
    </AdminLayout>
</template>

<script setup>
import DeliveryReceiptGrConfirmModal from '@/Components/DeliveryReceiptGrConfirmModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import DeliveryReceiptConfirmModal from '@/Components/FormConfirm/DeliveryReceiptConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import http from '@/utils/http';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    parties: Array,
    gr_sources: {
        type: Array,
        default: () => [],
    },
    gst_rate: {
        type: Number,
        default: 0,
    },
    gst_exemption_limit: {
        type: Number,
        default: 0,
    },
});

const partyOptions = ref([...(props.parties || [])]);
watch(() => props.parties, (v) => { partyOptions.value = [...(v || [])]; });

const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();
const grSourceKey = ref('');
const grFetchError = ref('');
const showGrConfirm = ref(false);
const pendingGrDetails = ref(null);
const consigneeName = ref('');
const modeOfPaymentEl = ref(null);

const grOptions = computed(() => props.gr_sources ?? []);

const form = useForm({
    delivery_receipt_no: '',
    date: new Date().toISOString().substr(0, 10),
    gr_no_bilty_no: '',
    mode_of_payment: '',
    consignee_id: '',
    haste: '',
    payment_due_from_id: '',
    freight: 0,
    handling_charge: 0,
    delivery_charge: 0,
    stationery_charge: 0,
    demurrage: 0,
    other_charge: 0,
    carriage_charge: 0,
    total_amount: 0,
    gst_on: 'total_charge',
    gst_rate: props.gst_rate,
    gst: 0,
    gst_pay_by: 'Consignee',
    less: 0,
    final_amount: 0,
});

watch(() => form.mode_of_payment, (mode) => {
    if (mode !== 'Due') {
        form.payment_due_from_id = '';
    } else if (!form.payment_due_from_id && form.consignee_id) {
        form.payment_due_from_id = form.consignee_id;
    }
});

const clearGrSelection = () => {
    grSourceKey.value = '';
    form.gr_no_bilty_no = '';
    form.consignee_id = '';
    consigneeName.value = '';
    pendingGrDetails.value = null;
};

const applyFetchedDetails = (data) => {
    Object.entries(data).forEach(([key, value]) => {
        if (Object.prototype.hasOwnProperty.call(form, key)) {
            form[key] = value ?? '';
        }
    });
    form.gst_rate = props.gst_rate;
    consigneeName.value = data.consignee_name || '';
    calculateTotals();
};

const onGrSourceChange = async () => {
    grFetchError.value = '';

    if (!grSourceKey.value) {
        clearGrSelection();

        return;
    }

    try {
        const { data } = await http.get('/delivery-receipts/gr-lookup', {
            params: { key: grSourceKey.value },
        });
        pendingGrDetails.value = data;
        showGrConfirm.value = true;
    } catch (error) {
        grFetchError.value = error.response?.data?.message || 'Could not load details for the selected GR.';
        clearGrSelection();
    }
};

const confirmGrSelection = async () => {
    if (pendingGrDetails.value) {
        applyFetchedDetails(pendingGrDetails.value);
    }
    showGrConfirm.value = false;
    await nextTick();
    modeOfPaymentEl.value?.focus();
};

const cancelGrSelection = () => {
    showGrConfirm.value = false;
    clearGrSelection();
};

const chargesSubTotal = computed(() => (
    parseFloat(form.freight || 0) +
    parseFloat(form.handling_charge || 0) +
    parseFloat(form.delivery_charge || 0) +
    parseFloat(form.stationery_charge || 0) +
    parseFloat(form.demurrage || 0) +
    parseFloat(form.other_charge || 0) +
    parseFloat(form.carriage_charge || 0)
));

const deliveryChargeGstBase = computed(() => (
    Math.max(chargesSubTotal.value - parseFloat(form.freight || 0), 0)
));

const gstTaxableBase = computed(() => (
    form.gst_on === 'delivery_charge'
        ? deliveryChargeGstBase.value
        : chargesSubTotal.value
));

const showGstFields = computed(() => gstTaxableBase.value > Number(props.gst_exemption_limit || 0));

const formatGstOnAmount = (amount) => Number(amount || 0).toFixed(2);

const calculateTotals = () => {
    const subTotal = chargesSubTotal.value;

    form.total_amount = subTotal.toFixed(2);

    const gstBase = gstTaxableBase.value;

    let rate = form.gst_pay_by === 'Exempted' ? 0 : parseFloat(form.gst_rate || 0);
    if (gstBase <= Number(props.gst_exemption_limit || 0)) {
        rate = 0;
    }
    const gstAmount = gstBase * (rate / 100);
    form.gst = gstAmount.toFixed(2);

    const gstInTotal = form.gst_pay_by === 'Transporter' ? gstAmount : 0;
    const finalTotal = subTotal + gstInTotal - parseFloat(form.less || 0);
    form.final_amount = finalTotal.toFixed(2);
};

const submit = () => {
    if (!form.gr_no_bilty_no) {
        grFetchError.value = 'Please select a GR number.';

        return;
    }

    calculateTotals();
    openConfirm();
};

const submitReceipt = (printAfterSave = false) => {
    confirmAndRun(() => {
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .post('/delivery-receipts', withPrintAfterSave(printAfterSave));
    });
};

const confirmSave = () => submitReceipt(false);
const confirmSaveAndPrint = () => submitReceipt(true);
</script>
