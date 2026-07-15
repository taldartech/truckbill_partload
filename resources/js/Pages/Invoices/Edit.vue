<template>
    <AdminLayout title="Edit Invoice">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Edit Invoice #{{ invoice.invoice_no }}</h3>
                        <a :href="`/invoices/${invoice.id}/print`" target="_blank" class="btn btn-sm btn-dark">
                            <i class="fas fa-print me-1"></i> Print
                        </a>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Invoice No.</label>
                                    <input type="text" class="form-control" v-model="form.invoice_no" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="form.date" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Party (Bill To) <span class="text-danger">*</span></label>
                                    <SearchableSelect
                                        v-model="form.party_id"
                                        :options="partyOptions"
                                        master="party"
                                        placeholder="Select Party"
                                        :disabled="!canChangeParty"
                                        @created="(record) => appendMasterOption(partyOptions, record)"
                                    />
                                    <p v-if="!canChangeParty" class="text-muted small mb-0 mt-1">
                                        Party cannot be changed after payment has been recorded.
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Payment Status</label>
                                    <div>
                                        <span class="badge" :class="statusBadgeClass(invoice.status)">
                                            {{ displayStatus(invoice.status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Amount Received</label>
                                    <div class="fw-bold text-success">₹ {{ formatAmount(paidAmount) }}</div>
                                </div>
                            </div>

                            <hr>
                            <h5 class="text-primary mb-3">TBB Bilties on Invoice</h5>
                            <p v-if="loadingBilties" class="text-muted small">Loading bilties...</p>

                            <div class="border rounded p-2 mb-3 bg-light">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th width="40"></th>
                                            <th>Bilty No</th>
                                            <th>Date</th>
                                            <th>From -> To</th>
                                            <th class="text-end">Freight</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="bilty in bilties"
                                            :key="bilty.id"
                                            :class="{ 'table-warning': isSelected(bilty.id) && !hasValidFreight(bilty) }"
                                        >
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    :checked="isSelected(bilty.id)"
                                                    @change="toggleBilty(bilty, $event)"
                                                >
                                            </td>
                                            <td>{{ bilty.bilty_number }}</td>
                                            <td>{{ bilty.date }}</td>
                                            <td>{{ bilty.from_station?.name }} -> {{ bilty.to_station?.name }}</td>
                                            <td class="text-end">
                                                <span v-if="isSelected(bilty.id) && !hasValidFreight(bilty)" class="badge bg-warning text-dark">Pending</span>
                                                <span v-else-if="isSelected(bilty.id)">₹ {{ formatAmount(displayFreight(bilty)) }}</span>
                                                <span v-else-if="needsPendingFreight(bilty)" class="text-muted small">On select</span>
                                                <span v-else>₹ {{ formatAmount(storedFreightTotal(bilty)) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span v-if="isSelected(bilty.id)">₹ {{ formatAmount(displayAmount(bilty)) }}</span>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!loadingBilties && bilties.length === 0">
                                            <td colspan="6" class="text-center text-muted">No bilties available for this party.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="form.bilty_ids.length > 0" class="border rounded p-3 mb-3 bg-white">
                                <h6 class="text-primary mb-3">Invoice Breakdown</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="small text-muted">Selected Bilties</div>
                                        <div class="fw-bold fs-5">{{ invoiceBreakdown.count }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small text-muted">Total Amount</div>
                                        <div class="fw-bold fs-5">₹ {{ formatAmount(invoiceBreakdown.total_amount) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small text-muted">GST Amount</div>
                                        <div class="fw-bold fs-5">₹ {{ formatAmount(invoiceBreakdown.gst_amount) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small text-muted">Net Amount</div>
                                        <div class="fw-bold fs-5 text-success">₹ {{ formatAmount(invoiceBreakdown.net_amount) }}</div>
                                    </div>
                                </div>
                                <p v-if="invoiceBreakdown.pending_freight > 0" class="text-danger small mb-0 mt-2">
                                    {{ invoiceBreakdown.pending_freight }} selected bilty(ies) still need freight.
                                </p>
                                <p v-if="paidAmount > invoiceBreakdown.net_amount" class="text-danger small mb-0 mt-2">
                                    Net amount cannot be less than amount already received (₹ {{ formatAmount(paidAmount) }}).
                                </p>
                            </div>

                            <div v-if="form.errors.bilty_ids" class="text-danger small mb-2">{{ form.errors.bilty_ids }}</div>
                            <div v-if="form.errors.bilty_detail_freight" class="text-danger small mb-2">{{ form.errors.bilty_detail_freight }}</div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Remark</label>
                                    <input type="text" class="form-control" v-model="form.remark">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2 bg-light">
                            <Link href="/invoices" class="btn btn-secondary">Cancel</Link>
                            <button
                                type="submit"
                                class="btn btn-warning"
                                :disabled="form.processing || form.bilty_ids.length === 0 || invoiceBreakdown.pending_freight > 0 || paidAmount > invoiceBreakdown.net_amount"
                            >
                                Update Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" ref="freightModalEl" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enter Freight — Bilty {{ modalBilty?.bilty_number }}</h5>
                        <button type="button" class="btn-close" @click="cancelFreightModal"></button>
                    </div>
                    <div class="modal-body" v-if="modalBilty">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Goods</th>
                                        <th class="text-center">Pkgs</th>
                                        <th class="text-center">Actual Wt</th>
                                        <th class="text-center">Charged Wt</th>
                                        <th>Frt. Type</th>
                                        <th class="text-center">Frt. Rate</th>
                                        <th class="text-end">Row Frt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in freightDetailInputs" :key="row.id">
                                        <td>{{ row.good_name || '—' }}</td>
                                        <td class="text-center">{{ row.no_of_packages ?? '—' }}</td>
                                        <td class="text-center">{{ row.gross_weight ?? '—' }}</td>
                                        <td class="text-center">{{ row.charged_weight ?? '—' }}</td>
                                        <td>
                                            <select
                                                class="form-select form-select-sm"
                                                v-model="row.rate_type"
                                                :class="{ 'is-invalid': freightModalErrors[`rate_type.${index}`] }"
                                                @change="calculateModalRow(index)"
                                            >
                                                <option value="Fixed">Fixed</option>
                                                <option value="Per Actual Weight">Per Actual Weight</option>
                                                <option value="Per Charged Weight">Per Charged Weight</option>
                                                <option value="Per Package">Per Package</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                class="form-control form-control-sm text-center"
                                                v-model="row.rate"
                                                :class="{ 'is-invalid': freightModalErrors[`rate.${index}`] }"
                                                @input="calculateModalRow(index)"
                                            >
                                        </td>
                                        <td class="text-end fw-bold">₹ {{ formatAmount(row.amount) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end fw-bold">Total Freight</td>
                                        <td class="text-end fw-bold text-success">₹ {{ formatAmount(modalFreightTotal) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-if="freightModalErrors.general" class="text-danger small mt-2">{{ freightModalErrors.general }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cancelFreightModal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="confirmFreightModal">Save & Select</button>
                    </div>
                </div>
            </div>
        </div>

        <InvoiceConfirmModal
            :show="showConfirm"
            :form="form"
            :parties="partyOptions"
            :breakdown="invoiceBreakdown"
            @cancel="closeConfirm"
            @confirm="confirmSave"
            @confirm-print="confirmSaveAndPrint"
        />
    </AdminLayout>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { appendMasterOption } from '@/composables/useMasterQuickAdd';
import InvoiceConfirmModal from '@/Components/FormConfirm/InvoiceConfirmModal.vue';
import { useFormConfirm } from '@/composables/useFormConfirm';
import { withPrintAfterSave, withPrintFlag } from '@/utils/formSubmit';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import http from '@/utils/http';
import { computed, ref, watch, onMounted } from 'vue';

const props = defineProps({
    invoice: Object,
    parties: Array,
});

const partyOptions = ref([...(props.parties || [])]);
watch(() => props.parties, (v) => { partyOptions.value = [...(v || [])]; });


const bilties = ref([]);
const loadingBilties = ref(false);
const freightModalEl = ref(null);
const { showConfirm, openConfirm, closeConfirm, confirmAndRun } = useFormConfirm();
const modalBilty = ref(null);
const freightDetailInputs = ref([]);
const freightModalErrors = ref({});
let freightModal = null;

const paidAmount = computed(() => parseFloat(props.invoice.paid_amount || 0));

const canChangeParty = computed(() => props.invoice.status === 'Pending');

const initialBiltyIds = props.invoice.details?.map((detail) => detail.bilty_id) || [];

const form = useForm({
    invoice_no: props.invoice.invoice_no,
    date: props.invoice.date,
    party_id: props.invoice.party_id,
    remark: props.invoice.remark || '',
    bilty_ids: [...initialBiltyIds],
    bilty_detail_freight: {},
});

onMounted(() => {
    if (typeof bootstrap !== 'undefined' && freightModalEl.value) {
        freightModal = new bootstrap.Modal(freightModalEl.value);
    }

    initializeFreightFromInvoice();
    fetchBilties(form.party_id);
});

const formatAmount = (value) => {
    const amount = parseFloat(value || 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
};

const displayStatus = (status) => status || 'Pending';

const statusBadgeClass = (status) => {
    const normalized = displayStatus(status);

    if (normalized === 'Paid') {
        return 'bg-success';
    }

    if (normalized === 'Partial') {
        return 'bg-warning text-dark';
    }

    return 'bg-danger';
};

const isSelected = (biltyId) => form.bilty_ids.includes(biltyId);

const calculateDetailAmount = (detail) => {
    const rate = parseFloat(detail.rate) || 0;
    const weight = parseFloat(detail.gross_weight) || 0;
    const chargedWeight = parseFloat(detail.charged_weight) || 0;
    const packages = parseFloat(detail.no_of_packages) || 0;

    if (detail.rate_type === 'Per Actual Weight' || detail.rate_type === 'Per KG') {
        return rate * weight;
    }
    if (detail.rate_type === 'Per Charged Weight') {
        return rate * chargedWeight;
    }
    if (detail.rate_type === 'Per Package') {
        return rate * packages;
    }

    return rate;
};

const biltyDetails = (bilty) => bilty.bilty_details || bilty.biltyDetails || [];

const buildDetailRows = (bilty) => {
    return biltyDetails(bilty).map((detail) => ({
        id: detail.id,
        good_name: detail.good?.name,
        no_of_packages: detail.no_of_packages,
        gross_weight: detail.gross_weight,
        charged_weight: detail.charged_weight,
        rate_type: detail.rate_type || 'Fixed',
        rate: detail.rate || '',
        amount: calculateDetailAmount({
            ...detail,
            rate_type: detail.rate_type || 'Fixed',
            rate: detail.rate || 0,
        }),
    }));
};

const initializeFreightFromInvoice = () => {
    props.invoice.details?.forEach((detail) => {
        const bilty = detail.bilty;
        if (bilty?.id) {
            form.bilty_detail_freight[bilty.id] = buildDetailRows(bilty);
        }
    });
};

const storedFreightTotal = (bilty) => {
    return biltyDetails(bilty).reduce((sum, detail) => sum + calculateDetailAmount(detail), 0);
};

const hasDetailFreightInForm = (bilty) => {
    const rows = form.bilty_detail_freight[bilty.id];
    if (!rows?.length) {
        return false;
    }

    return rows.reduce((sum, row) => sum + calculateDetailAmount(row), 0) > 0;
};

const needsPendingFreight = (bilty) => storedFreightTotal(bilty) <= 0;

const displayFreight = (bilty) => {
    if (!isSelected(bilty.id)) {
        return 0;
    }

    if (hasDetailFreightInForm(bilty)) {
        return form.bilty_detail_freight[bilty.id].reduce((sum, row) => sum + calculateDetailAmount(row), 0);
    }

    return storedFreightTotal(bilty);
};

const hasValidFreight = (bilty) => isSelected(bilty.id) && displayFreight(bilty) > 0;

const addonTotal = (bilty) => {
    return (bilty.addon_charges || []).reduce((sum, charge) => sum + (parseFloat(charge.amount) || 0), 0);
};

const amountBreakdown = (bilty) => {
    const freight = displayFreight(bilty);
    const subtotal = freight
        + (parseFloat(bilty.bilty_charge) || 0)
        + (parseFloat(bilty.surcharge) || 0)
        + (parseFloat(bilty.handling_charge) || 0)
        + (parseFloat(bilty.risk_dd_charge) || 0)
        + (parseFloat(bilty.cartage) || 0)
        + addonTotal(bilty)
        - (parseFloat(bilty.less_advance_received) || 0);
    const gstRate = bilty.gst_liability_by === 'Exempted' ? 0 : (parseFloat(bilty.gst) || 0);
    const gstAmount = subtotal * (gstRate / 100);

    return {
        subtotal,
        gst_amount: gstAmount,
        net_amount: subtotal + gstAmount,
    };
};

const displayAmount = (bilty) => amountBreakdown(bilty).net_amount;

const selectedBilties = () => bilties.value.filter((bilty) => form.bilty_ids.includes(bilty.id));

const invoiceBreakdown = computed(() => {
    const selected = selectedBilties();

    return {
        count: selected.length,
        total_amount: selected.reduce((sum, bilty) => sum + amountBreakdown(bilty).subtotal, 0),
        gst_amount: selected.reduce((sum, bilty) => sum + amountBreakdown(bilty).gst_amount, 0),
        net_amount: selected.reduce((sum, bilty) => sum + amountBreakdown(bilty).net_amount, 0),
        pending_freight: selected.filter((bilty) => !hasValidFreight(bilty)).length,
    };
});

const modalFreightTotal = computed(() => {
    return freightDetailInputs.value.reduce((sum, row) => sum + (parseFloat(row.amount) || 0), 0);
});

const selectBilty = (bilty) => {
    if (!form.bilty_ids.includes(bilty.id)) {
        form.bilty_ids = [...form.bilty_ids, bilty.id];
    }

    if (!form.bilty_detail_freight[bilty.id]) {
        form.bilty_detail_freight[bilty.id] = buildDetailRows(bilty);
    }
};

const deselectBilty = (biltyId) => {
    form.bilty_ids = form.bilty_ids.filter((id) => id !== biltyId);
    delete form.bilty_detail_freight[biltyId];
};

const openFreightModal = (bilty) => {
    modalBilty.value = bilty;
    freightDetailInputs.value = form.bilty_detail_freight[bilty.id]?.length
        ? JSON.parse(JSON.stringify(form.bilty_detail_freight[bilty.id]))
        : buildDetailRows(bilty);
    freightModalErrors.value = {};
    freightModal?.show();
};

const hideFreightModal = () => {
    freightModal?.hide();
    modalBilty.value = null;
    freightDetailInputs.value = [];
    freightModalErrors.value = {};
};

const toggleBilty = (bilty, event) => {
    const checked = event.target.checked;

    if (!checked) {
        deselectBilty(bilty.id);
        return;
    }

    if (needsPendingFreight(bilty) && !hasDetailFreightInForm(bilty)) {
        event.target.checked = false;
        openFreightModal(bilty);
        return;
    }

    selectBilty(bilty);
};

const calculateModalRow = (index) => {
    const row = freightDetailInputs.value[index];
    if (!row) {
        return;
    }

    row.amount = calculateDetailAmount(row);
};

const confirmFreightModal = () => {
    freightModalErrors.value = {};
    let valid = true;

    freightDetailInputs.value.forEach((row, index) => {
        const rate = parseFloat(row.rate);
        if (!row.rate_type) {
            freightModalErrors.value[`rate_type.${index}`] = 'Required';
            valid = false;
        }
        if (!Number.isFinite(rate) || rate <= 0) {
            freightModalErrors.value[`rate.${index}`] = 'Required';
            valid = false;
        }
    });

    if (modalFreightTotal.value <= 0) {
        freightModalErrors.value.general = 'Total freight must be greater than zero.';
        valid = false;
    }

    if (!valid || !modalBilty.value) {
        return;
    }

    form.bilty_detail_freight[modalBilty.value.id] = freightDetailInputs.value.map((row) => ({
        id: row.id,
        good_name: row.good_name,
        no_of_packages: row.no_of_packages,
        gross_weight: row.gross_weight,
        charged_weight: row.charged_weight,
        rate_type: row.rate_type,
        rate: parseFloat(row.rate),
        amount: calculateDetailAmount(row),
    }));

    selectBilty(modalBilty.value);
    hideFreightModal();
};

const cancelFreightModal = () => {
    hideFreightModal();
};

const fetchBilties = async (partyId) => {
    if (!partyId) {
        bilties.value = [];
        return;
    }

    loadingBilties.value = true;

    try {
        const { data } = await http.get('/invoices/pending-bilties', {
            params: {
                party_id: partyId,
                invoice_id: props.invoice.id,
            },
        });
        bilties.value = data.bilties || [];
    } catch {
        bilties.value = [];
    } finally {
        loadingBilties.value = false;
    }
};

watch(() => form.party_id, (partyId, oldPartyId) => {
    if (!canChangeParty.value) {
        return;
    }

    if (oldPartyId && partyId !== oldPartyId) {
        form.bilty_ids = [];
        form.bilty_detail_freight = {};
    }

    fetchBilties(partyId);
});

const prepareFreightPayload = () => {
    const payload = {};

    form.bilty_ids.forEach((biltyId) => {
        payload[biltyId] = (form.bilty_detail_freight[biltyId] || []).map((row) => ({
            id: row.id,
            rate_type: row.rate_type,
            rate: parseFloat(row.rate) || 0,
        }));
    });

    form.bilty_detail_freight = payload;
};

const submit = () => {
    if (invoiceBreakdown.value.pending_freight > 0 || paidAmount.value > invoiceBreakdown.value.net_amount) {
        return;
    }

    openConfirm();
};

const invoicePrintUrl = `/invoices/${props.invoice.id}/print`;

const submitInvoice = (printAfterSave = false) => {
    confirmAndRun(() => {
        prepareFreightPayload();
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .put(`/invoices/${props.invoice.id}`, withPrintAfterSave(printAfterSave, invoicePrintUrl));
    });
};

const confirmSave = () => submitInvoice(false);
const confirmSaveAndPrint = () => submitInvoice(true);
</script>
