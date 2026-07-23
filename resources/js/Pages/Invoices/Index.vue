<template>
    <AdminLayout title="Invoice">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Invoice Management</h3>
                    <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                        <Link
                            v-if="can('create invoices')"
                            href="/invoices/create"
                            class="btn btn-sm app-index-header-btn"
                        >
                            <span class="app-index-header-btn__icon app-index-header-btn__icon--plus" aria-hidden="true"></span>
                            Create
                        </Link>
                    </div>
                </div>

                <AppIndexCollapsibleFilters
                    panel-id="invoices-filters-panel"
                    :loading="searching"
                    @search="applyFilters"
                    @reset="resetFilters"
                >
                    <div class="filter-search-col">
                        <label class="form-label" for="invoices_search">Search</label>
                        <AppIndexSearchInput
                            v-model="filters.search"
                            input-id="invoices_search"
                            placeholder="Invoice number"
                            max-width="100%"
                            size="md"
                            :disabled="searching"
                            @keydown.enter="applyFilters"
                        />
                    </div>

                    <div class="filter-period-col">
                        <IndexDateRangePicker
                            v-model:from-date="filters.from_date"
                            v-model:to-date="filters.to_date"
                            label="Invoice Date"
                            panel-align="start"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label">Party</label>
                        <SearchableSelect
                            v-model="filters.party_id"
                            :options="partyOptions"
                            placeholder="All parties"
                            :can-create="false"
                            :disabled="searching"
                        />
                    </div>

                    <div class="filter-picker-col">
                        <label class="form-label" for="invoices_status">Status</label>
                        <select
                            id="invoices_status"
                            class="form-select"
                            v-model="filters.status"
                            :disabled="searching"
                        >
                            <option value="">All statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Partial">Partial</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                </AppIndexCollapsibleFilters>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Invoice No.</th>
                                    <th>Date</th>
                                    <th>Party Name</th>
                                    <th>Total Amount</th>
                                    <th>Paid</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in invoices.data" :key="invoice.id">
                                    <td>{{ invoice.id }}</td>
                                    <td>{{ invoice.invoice_no }}</td>
                                    <td>{{ invoice.date }}</td>
                                    <td>{{ invoice.party?.name }}</td>
                                    <td class="fw-bold">₹ {{ formatAmount(invoice.net_amount) }}</td>
                                    <td>₹ {{ formatAmount(invoice.paid_amount) }}</td>
                                    <td>
                                        <span class="badge" :class="statusBadgeClass(invoice.status)">
                                            {{ displayStatus(invoice.status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button
                                            v-if="can('edit invoices')"
                                            type="button"
                                            class="btn btn-sm btn-outline-success me-2"
                                            title="Record Payment"
                                            @click="openPaymentModal(invoice)"
                                        >
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                        <a
                                            :href="`/invoices/${invoice.id}/print`"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-info me-2"
                                            title="Print Invoice"
                                        >
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link
                                            v-if="can('edit invoices') && invoice.can_edit"
                                            :href="`/invoices/${invoice.id}/edit`"
                                            class="btn btn-sm btn-info text-white me-2"
                                            title="Edit Invoice"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button
                                            v-if="can('delete invoices') && invoice.can_delete"
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            title="Delete Invoice"
                                            @click="deleteInvoice(invoice.id)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No invoices found for selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PaginationLinks :pagination="invoices" />
            </div>
        </div>

        <div class="modal fade" ref="paymentModalEl" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" @click="hidePaymentModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="paymentLoading" class="text-muted">Loading payment details...</div>
                        <template v-else-if="paymentInvoice">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="small text-muted">Invoice</div>
                                    <div class="fw-bold">{{ paymentInvoice.invoice_no }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted">Party</div>
                                    <div class="fw-bold">{{ paymentInvoice.party_name || '—' }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="small text-muted">Net Amount</div>
                                    <div class="fw-bold">₹ {{ formatAmount(paymentInvoice.net_amount) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Paid</div>
                                    <div class="fw-bold text-success">₹ {{ formatAmount(paymentInvoice.paid_amount) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Balance</div>
                                    <div class="fw-bold text-danger">₹ {{ formatAmount(paymentInvoice.balance_amount) }}</div>
                                </div>
                            </div>

                            <div v-if="paymentRows.length" class="mb-3">
                                <h6 class="mb-2">Previous Payments</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Mode</th>
                                                <th>Reference</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="payment in paymentRows" :key="payment.id">
                                                <td>{{ payment.date }}</td>
                                                <td>{{ payment.payment_mode }}</td>
                                                <td>{{ payment.reference_no || '—' }}</td>
                                                <td class="text-end">₹ {{ formatAmount(payment.amount) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-if="paymentInvoice.balance_amount > 0">
                                <h6 class="mb-2">Add Payment</h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" v-model="paymentForm.date">
                                        <div v-if="paymentForm.errors.date" class="text-danger small">{{ paymentForm.errors.date }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Amount</label>
                                        <input type="number" step="0.01" min="0.01" class="form-control" v-model="paymentForm.amount" :max="paymentInvoice.balance_amount">
                                        <div v-if="paymentForm.errors.amount" class="text-danger small">{{ paymentForm.errors.amount }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Payment Mode</label>
                                        <select class="form-select" v-model="paymentForm.payment_mode">
                                            <option value="Cash">Cash</option>
                                            <option value="Cheque">Cheque</option>
                                            <option value="RTGS">RTGS</option>
                                            <option value="NEFT">NEFT</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Bank">Bank</option>
                                        </select>
                                        <div v-if="paymentForm.errors.payment_mode" class="text-danger small">{{ paymentForm.errors.payment_mode }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reference No.</label>
                                        <input type="text" class="form-control" v-model="paymentForm.reference_no" placeholder="Cheque / txn ref">
                                        <div v-if="paymentForm.errors.reference_no" class="text-danger small">{{ paymentForm.errors.reference_no }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Remark</label>
                                        <input type="text" class="form-control" v-model="paymentForm.remark">
                                        <div v-if="paymentForm.errors.remark" class="text-danger small">{{ paymentForm.errors.remark }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="alert alert-success mb-0">This invoice is fully paid.</div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="hidePaymentModal">Close</button>
                        <button
                            v-if="paymentInvoice && paymentInvoice.balance_amount > 0"
                            type="button"
                            class="btn btn-success"
                            :disabled="paymentForm.processing"
                            @click="submitPayment"
                        >
                            Save Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppIndexCollapsibleFilters from '@/Components/Index/AppIndexCollapsibleFilters.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';
import IndexDateRangePicker from '@/Components/Index/IndexDateRangePicker.vue';
import { thisMonthDateRange } from '@/utils/dateRangePresets';
import http from '@/utils/http';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

const props = defineProps({
    invoices: { type: Object, required: true },
    parties: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const defaultRange = thisMonthDateRange();
const searching = ref(false);
const deleteForm = useForm({});
const paymentModalEl = ref(null);
const paymentLoading = ref(false);
const paymentInvoice = ref(null);
const paymentRows = ref([]);
const selectedInvoiceId = ref(null);
let paymentModal = null;

const paymentForm = useForm({
    date: new Date().toISOString().substr(0, 10),
    amount: '',
    payment_mode: 'Cash',
    reference_no: '',
    remark: '',
});

const filters = reactive({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? defaultRange.from_date,
    to_date: props.filters.to_date ?? defaultRange.to_date,
    party_id: props.filters.party_id ?? '',
    status: props.filters.status ?? '',
});

watch(
    () => props.filters,
    (next) => {
        filters.search = next.search ?? '';
        filters.from_date = next.from_date ?? defaultRange.from_date;
        filters.to_date = next.to_date ?? defaultRange.to_date;
        filters.party_id = next.party_id ?? '';
        filters.status = next.status ?? '';
    },
    { deep: true },
);

const partyOptions = computed(() => props.parties);

onMounted(() => {
    if (typeof bootstrap !== 'undefined' && paymentModalEl.value) {
        paymentModal = new bootstrap.Modal(paymentModalEl.value);
    }
});

const buildQuery = () => {
    const query = {
        search: filters.search.trim() || undefined,
        from_date: filters.from_date || undefined,
        to_date: filters.to_date || undefined,
        party_id: filters.party_id || undefined,
        status: filters.status || undefined,
    };

    return Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== undefined && value !== ''),
    );
};

const applyFilters = () => {
    searching.value = true;
    router.get('/invoices', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            searching.value = false;
        },
    });
};

const resetFilters = () => {
    const range = thisMonthDateRange();
    filters.search = '';
    filters.from_date = range.from_date;
    filters.to_date = range.to_date;
    filters.party_id = '';
    filters.status = '';
    applyFilters();
};

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

const resetPaymentForm = () => {
    paymentForm.reset();
    paymentForm.date = new Date().toISOString().substr(0, 10);
    paymentForm.payment_mode = 'Cash';
};

const loadPaymentDetails = async (invoiceId) => {
    paymentLoading.value = true;
    paymentInvoice.value = null;
    paymentRows.value = [];

    try {
        const { data } = await http.get(`/invoices/${invoiceId}/payments`);
        paymentInvoice.value = data.invoice;
        paymentRows.value = data.payments || [];
    } catch {
        paymentInvoice.value = null;
        paymentRows.value = [];
    } finally {
        paymentLoading.value = false;
    }
};

const openPaymentModal = async (invoice) => {
    selectedInvoiceId.value = invoice.id;
    resetPaymentForm();
    paymentModal?.show();
    await loadPaymentDetails(invoice.id);
};

const hidePaymentModal = () => {
    paymentModal?.hide();
};

const submitPayment = () => {
    if (!selectedInvoiceId.value) {
        return;
    }

    paymentForm.post(`/invoices/${selectedInvoiceId.value}/payments`, {
        preserveScroll: true,
        onSuccess: () => {
            hidePaymentModal();
        },
    });
};

const deleteInvoice = (id) => {
    if (confirm('Are you sure you want to delete this invoice? Only the latest pending invoice can be deleted.')) {
        deleteForm.delete(`/invoices/${id}`);
    }
};
</script>

<style scoped>
:deep(.app-index-collapsible-filters__inner) {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: flex-end !important;
    gap: 10px !important;
}

:deep(.app-index-filters-fields) {
    display: contents !important;
}

:deep(.app-index-collapsible-filters__actions) {
    display: flex !important;
    flex-wrap: nowrap !important;
    margin-top: 0 !important;
    margin-left: auto !important;
    align-self: flex-end !important;
    flex: 0 0 auto !important;
    gap: 0.5rem;
}

.filter-search-col,
.filter-period-col,
.filter-picker-col {
    min-width: 0 !important;
}

.filter-search-col {
    flex: 1 1 200px !important;
    width: auto !important;
    max-width: 240px !important;
}

.filter-period-col {
    flex: 0 1 180px !important;
    width: 180px !important;
    max-width: 180px !important;
}

.filter-picker-col {
    flex: 1 1 170px !important;
    width: auto !important;
    max-width: 210px !important;
}

.filter-search-col :deep(.app-index-search),
.filter-period-col :deep(.dashboard-date-range),
.filter-picker-col :deep(.searchable-select),
.filter-picker-col :deep(.form-select) {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}

.filter-search-col :deep(.app-index-search .input-group-text),
.filter-search-col :deep(.app-index-search .form-control),
.filter-period-col :deep(.dashboard-date-range__trigger),
.filter-picker-col :deep(.searchable-select__trigger),
.filter-picker-col :deep(.form-select),
:deep(.app-index-collapsible-filters__btn) {
    height: 36px !important;
    min-height: 36px !important;
}

.filter-search-col :deep(.app-index-search .input-group-text) {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    line-height: 1 !important;
}

.filter-picker-col :deep(.searchable-select__trigger) {
    width: 100% !important;
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
}

.filter-search-col > .form-label,
.filter-picker-col > .form-label,
.filter-period-col :deep(.dashboard-date-range__label) {
    margin-bottom: 0.35rem;
    white-space: nowrap;
}

@media (max-width: 991.98px) {
    :deep(.app-index-collapsible-filters__inner) {
        flex-wrap: wrap !important;
    }

    .filter-search-col,
    .filter-period-col,
    .filter-picker-col {
        flex: 1 1 calc(50% - 10px) !important;
        width: auto !important;
        max-width: none !important;
    }

    :deep(.app-index-collapsible-filters__actions) {
        margin-left: 0 !important;
        width: 100%;
        justify-content: flex-end;
    }
}
</style>
