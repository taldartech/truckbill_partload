<template>
    <AdminLayout title="Edit Delivery Receipt">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Delivery Receipt</h3>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Receipt No. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.delivery_receipt_no" required>
                                    <div v-if="form.errors.delivery_receipt_no" class="text-danger small mt-1">{{ form.errors.delivery_receipt_no }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">GR No. <span class="text-danger">*</span></label>
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
                                <div class="col-md-3">
                                    <label class="form-label">Mode of Payment <span class="text-danger">*</span></label>
                                    <select
                                        ref="modeOfPaymentEl"
                                        class="form-select form-select-sm"
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
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Consignee <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm bg-light"
                                        :value="consigneeName"
                                        readonly
                                        tabindex="-1"
                                    >
                                    <div v-if="form.errors.consignee_id" class="text-danger small mt-1">{{ form.errors.consignee_id }}</div>
                                </div>
                                <div v-if="form.mode_of_payment === 'Due'" class="col-md-4">
                                    <label class="form-label">Payment Due From <span class="text-danger">*</span></label>
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
                                <div class="col-md-4">
                                    <label class="form-label">Haste</label>
                                    <input type="text" class="form-control form-control-sm" v-model="form.haste">
                                </div>
                            </div>

                            <hr>
                            <h6 class="mb-3 text-warning">Charges Breakdown</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Freight</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.freight" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Handling Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.handling_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Delivery Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.delivery_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Stationery Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.stationery_charge" @input="calculateTotals"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 border-start">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Demurrage</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.demurrage" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Other Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.other_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0">Carriage Charge</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm" v-model="form.carriage_charge" @input="calculateTotals"></div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-5"><label class="form-label mb-0 fw-bold">Sub Total Amount</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control form-control-sm bg-light" v-model="form.total_amount" readonly></div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="mb-3 text-warning">GST & Final Calculation</h6>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">GST On</label>
                                    <select class="form-select form-select-sm" v-model="form.gst_on" @change="calculateTotals">
                                        <option value="delivery_charge">Delivery Charge</option>
                                        <option value="total_charge">Total Charge</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">GST ({{ Number(form.gst_rate || 0) }}%)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm bg-light" v-model="form.gst" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">GST Pay By</label>
                                    <select class="form-select form-select-sm" v-model="form.gst_pay_by" @change="calculateTotals">
                                        <option value="Consignor">Consignor</option>
                                        <option value="Consignee">Consignee</option>
                                        <option value="Transporter">Transporter</option>
                                        <option value="Exempted">Exempted</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Less</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" v-model="form.less" @input="calculateTotals">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8"></div>
                                <div class="col-md-4">
                                    <div class="row align-items-center border p-2 bg-light rounded">
                                        <div class="col-5"><label class="form-label mb-0 fw-bold fs-5 text-danger">Final Amount</label></div>
                                        <div class="col-7"><input type="number" step="0.01" class="form-control fw-bold text-danger fs-5" v-model="form.final_amount" readonly></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/delivery-receipts" class="btn btn-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-warning" :disabled="form.processing">Update</button>
                        </div>
                    </form>
                </div>
            </div>
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
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    receipt: Object,
    parties: Array,
    gr_sources: {
        type: Array,
        default: () => [],
    },
    gst_rate: {
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
const consigneeName = ref(props.receipt.consignee?.name || '');
const modeOfPaymentEl = ref(null);

const form = useForm({
    delivery_receipt_no: props.receipt.delivery_receipt_no || '',
    date: props.receipt.date || new Date().toISOString().substr(0, 10),
    gr_no_bilty_no: props.receipt.gr_no_bilty_no || '',
    mode_of_payment: props.receipt.mode_of_payment === 'To Pay'
        ? 'Due'
        : (props.receipt.mode_of_payment || ''),
    consignee_id: props.receipt.consignee_id || '',
    haste: props.receipt.haste || '',
    payment_due_from_id: props.receipt.payment_due_from_id || '',
    freight: props.receipt.freight || 0,
    handling_charge: props.receipt.handling_charge || 0,
    delivery_charge: props.receipt.delivery_charge || 0,
    stationery_charge: props.receipt.stationery_charge || 0,
    demurrage: props.receipt.demurrage || 0,
    other_charge: props.receipt.other_charge || 0,
    carriage_charge: props.receipt.carriage_charge || 0,
    total_amount: props.receipt.total_amount || 0,
    gst_on: props.receipt.gst_on || 'total_charge',
    gst_rate: props.gst_rate,
    gst: props.receipt.gst || 0,
    gst_pay_by: props.receipt.gst_pay_by || 'Consignee',
    less: props.receipt.less || 0,
    final_amount: props.receipt.final_amount || 0,
});

const grOptions = computed(() => {
    const options = [...(props.gr_sources ?? [])];
    const current = form.gr_no_bilty_no;

    if (current && !options.some((option) => option.gr_no === current || option.label === current)) {
        const name = consigneeName.value || '—';
        options.unshift({
            id: `saved:${current}`,
            label: `${current} (${name})`,
            gr_no: current,
            consignee_name: name,
            type: 'Saved',
        });
    }

    return options;
});

watch(() => form.mode_of_payment, (mode) => {
    if (mode !== 'Due') {
        form.payment_due_from_id = '';
    } else if (!form.payment_due_from_id && form.consignee_id) {
        form.payment_due_from_id = form.consignee_id;
    }
});

onMounted(() => {
    const current = form.gr_no_bilty_no;
    const match = grOptions.value.find((option) => option.gr_no === current || option.label === current);
    if (match) {
        grSourceKey.value = match.id;
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

    if (grSourceKey.value.startsWith('saved:')) {
        form.gr_no_bilty_no = grSourceKey.value.replace(/^saved:/, '');

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

const calculateTotals = () => {
    const subTotal =
        parseFloat(form.freight || 0) +
        parseFloat(form.handling_charge || 0) +
        parseFloat(form.delivery_charge || 0) +
        parseFloat(form.stationery_charge || 0) +
        parseFloat(form.demurrage || 0) +
        parseFloat(form.other_charge || 0) +
        parseFloat(form.carriage_charge || 0);

    form.total_amount = subTotal.toFixed(2);

    const gstBase = form.gst_on === 'delivery_charge'
        ? parseFloat(form.delivery_charge || 0)
        : subTotal;

    const rate = form.gst_pay_by === 'Exempted' ? 0 : parseFloat(form.gst_rate || 0);
    const gstAmount = gstBase * (rate / 100);
    form.gst = gstAmount.toFixed(2);

    const finalTotal = subTotal + gstAmount - parseFloat(form.less || 0);
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

const receiptPrintUrl = `/delivery-receipts/${props.receipt.id}/print`;

const submitReceipt = (printAfterSave = false) => {
    confirmAndRun(() => {
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .put(`/delivery-receipts/${props.receipt.id}`, withPrintAfterSave(printAfterSave, receiptPrintUrl));
    });
};

const confirmSave = () => submitReceipt(false);
const confirmSaveAndPrint = () => submitReceipt(true);
</script>
