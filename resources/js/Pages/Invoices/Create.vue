<template>
    <AdminLayout title="Create Invoice">
        <div class="bilty-form-page">
            <form @submit.prevent="submit" class="bilty-form">
                <!-- 1. Invoice Details -->
                <section class="bilty-section">
                    <div class="bilty-section__head">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num">1</div>
                            <div>
                                <h2 class="bilty-section__title">Invoice Details</h2>
                                <p class="bilty-section__desc">Party and invoice identification</p>
                            </div>
                        </div>
                        <div class="bilty-section__head-right">
                            <div class="bilty-id">
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Invoice No.<span class="text-danger">*</span></span>
                                    <input type="text" class="form-control form-control-sm" v-model="form.invoice_no" required>
                                </div>
                                <div class="bilty-id__field">
                                    <span class="bilty-id__label">Date<span class="text-danger">*</span></span>
                                    <input type="date" class="form-control form-control-sm" v-model="form.date" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--2">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Party (Bill To)<span class="bilty-field__req">*</span></label>
                                <SearchableSelect
                                    :small="true"
                                    v-model="form.party_id"
                                    :options="partyOptions"
                                    master="party"
                                    placeholder="Select Party"
                                    @created="(record) => appendMasterOption(partyOptions, record)"
                                />
                                <div v-if="form.errors.party_id" class="text-danger small mt-1">{{ form.errors.party_id }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Select TBB Bilties -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--green">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--green">2</div>
                            <div>
                                <h2 class="bilty-section__title">Select TBB Bilties</h2>
                                <p v-if="!form.party_id" class="bilty-section__desc mb-0">Select a party to load pending TBB bilties.</p>
                                <p v-else-if="loadingBilties" class="bilty-section__desc mb-0">Loading bilties...</p>
                                <p v-else class="bilty-section__desc mb-0">Choose bilties to include on this invoice</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body bilty-section__body--flush">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0 table-grid">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40" class="text-center"></th>
                                        <th>Bilty No.</th>
                                        <th>Date</th>
                                        <th>From &rarr; To</th>
                                        <th class="text-end">Freight</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="bilty in bilties"
                                        :key="bilty.id"
                                        class="bg-white"
                                        :class="{ 'table-warning': isSelected(bilty.id) && !hasValidFreight(bilty) }"
                                    >
                                        <td class="text-center align-middle">
                                            <input
                                                type="checkbox"
                                                :checked="isSelected(bilty.id)"
                                                @change="toggleBilty(bilty, $event)"
                                            >
                                        </td>
                                        <td class="align-middle fw-semibold">{{ bilty.bilty_number }}</td>
                                        <td class="align-middle">{{ bilty.date }}</td>
                                        <td class="align-middle">{{ bilty.from_station?.name }} &rarr; {{ bilty.to_station?.name }}</td>
                                        <td class="text-end align-middle">
                                            <span v-if="isSelected(bilty.id) && !hasValidFreight(bilty)" class="badge bg-warning text-dark">Pending</span>
                                            <span v-else-if="isSelected(bilty.id)">₹ {{ formatAmount(displayFreight(bilty)) }}</span>
                                            <span v-else-if="needsPendingFreight(bilty)" class="text-muted small">On select</span>
                                            <span v-else>₹ {{ formatAmount(storedFreightTotal(bilty)) }}</span>
                                        </td>
                                        <td class="text-end align-middle">
                                            <span v-if="isSelected(bilty.id)">₹ {{ formatAmount(displayAmount(bilty)) }}</span>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                    </tr>
                                    <tr v-if="form.party_id && !loadingBilties && bilties.length === 0">
                                        <td colspan="6" class="text-center text-muted py-3">No pending TBB bilties found for this party.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="form.errors.bilty_ids" class="text-danger small p-2">{{ form.errors.bilty_ids }}</div>
                        <div v-if="form.errors.bilty_detail_freight" class="text-danger small px-2 pb-2">{{ form.errors.bilty_detail_freight }}</div>
                    </div>
                </section>

                <!-- 3. Summary -->
                <section class="bilty-section">
                    <div class="bilty-section__head bilty-section__head--orange">
                        <div class="bilty-section__head-left">
                            <div class="bilty-step-num bilty-step-num--orange">3</div>
                            <div>
                                <h2 class="bilty-section__title">Summary</h2>
                                <p class="bilty-section__desc">Totals and remarks</p>
                            </div>
                        </div>
                    </div>
                    <div class="bilty-section__body">
                        <div class="bilty-form-grid bilty-form-grid--4">
                            <div class="bilty-field">
                                <label class="bilty-field__label">Selected Bilties</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="invoiceBreakdown.count" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Total Amount</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="'₹ ' + formatAmount(invoiceBreakdown.total_amount)" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">GST Amount</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" :value="'₹ ' + formatAmount(invoiceBreakdown.gst_amount)" readonly>
                            </div>
                            <div class="bilty-field">
                                <label class="bilty-field__label">Net Amount</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold text-success" :value="'₹ ' + formatAmount(invoiceBreakdown.net_amount)" readonly>
                            </div>
                        </div>
                        <p v-if="form.bilty_ids.length > 0 && invoiceBreakdown.pending_freight > 0" class="text-danger small mb-2 mt-2">
                            {{ invoiceBreakdown.pending_freight }} selected bilty(ies) still need freight.
                        </p>
                        <div class="bilty-field mt-2">
                            <label class="bilty-field__label">Remark</label>
                            <input type="text" class="form-control form-control-sm" v-model="form.remark">
                        </div>
                    </div>
                </section>

                <div class="bilty-form-actions">
                    <Link href="/invoices" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i> Cancel
                    </Link>
                    <button
                        type="submit"
                        class="btn btn-success btn-sm px-4"
                        :disabled="form.processing || !form.party_id || form.bilty_ids.length === 0 || invoiceBreakdown.pending_freight > 0"
                    >
                        <i class="fas fa-file-invoice me-1"></i> Generate Invoice
                    </button>
                </div>
            </form>
        </div>

        <div class="modal fade" ref="freightModalEl" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enter Freight — Bilty {{ modalBilty?.bilty_number }}</h5>
                        <button type="button" class="btn-close" @click="cancelFreightModal"></button>
                    </div>
                    <div class="modal-body" v-if="modalBilty">
                        <p class="text-muted small mb-3">
                            {{ modalBilty.date }} · {{ modalBilty.from_station?.name }} -> {{ modalBilty.to_station?.name }}
                        </p>
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
    parties: Array,
    next_invoice_no: {
        type: String,
        default: '',
    },
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

const form = useForm({
    invoice_no: props.next_invoice_no || '',
    date: new Date().toISOString().substr(0, 10),
    party_id: '',
    remark: '',
    bilty_ids: [],
    bilty_detail_freight: {},
});

onMounted(() => {
    if (typeof bootstrap !== 'undefined' && freightModalEl.value) {
        freightModal = new bootstrap.Modal(freightModalEl.value);
    }
});

const formatAmount = (value) => {
    const amount = parseFloat(value || 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
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
    const gstInTotal = bilty.gst_liability_by === 'Transporter' ? gstAmount : 0;

    return {
        subtotal,
        gst_amount: gstAmount,
        net_amount: subtotal + gstInTotal,
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
    freightDetailInputs.value = buildDetailRows(bilty);
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
        form.bilty_ids = [];
        form.bilty_detail_freight = {};
        return;
    }

    loadingBilties.value = true;
    form.bilty_ids = [];
    form.bilty_detail_freight = {};

    try {
        const { data } = await http.get('/invoices/pending-bilties', {
            params: { party_id: partyId },
        });
        bilties.value = data.bilties || [];
    } catch {
        bilties.value = [];
    } finally {
        loadingBilties.value = false;
    }
};

watch(() => form.party_id, (partyId) => {
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
    if (invoiceBreakdown.value.pending_freight > 0) {
        return;
    }

    openConfirm();
};

const submitInvoice = (printAfterSave = false) => {
    confirmAndRun(() => {
        prepareFreightPayload();
        form.transform((data) => withPrintFlag(data, printAfterSave))
            .post('/invoices', withPrintAfterSave(printAfterSave));
    });
};

const confirmSave = () => submitInvoice(false);
const confirmSaveAndPrint = () => submitInvoice(true);
</script>
