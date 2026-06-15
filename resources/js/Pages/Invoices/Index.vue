<template>
    <AdminLayout title="Invoice">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Invoices</h3>
                        <Link v-if="can('create invoices')" href="/invoices/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Invoice
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
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
                                            class="btn btn-sm btn-outline-success me-2"
                                            title="Record Payment"
                                            @click="openPaymentModal(invoice)"
                                        >
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                        <a :href="`/invoices/${invoice.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-2" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link v-if="can('edit invoices') && invoice.can_edit" :href="`/invoices/${invoice.id}/edit`" class="btn btn-sm btn-info text-white me-2" title="Edit Invoice">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button
                                            v-if="can('delete invoices') && invoice.can_delete"
                                            class="btn btn-sm btn-danger"
                                            title="Delete Invoice"
                                            @click="deleteInvoice(invoice.id)"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="8" class="text-center py-4">No invoices found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import http from '@/utils/http';
import { onMounted, ref } from 'vue';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    invoices: Object,
});

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

onMounted(() => {
    if (typeof bootstrap !== 'undefined' && paymentModalEl.value) {
        paymentModal = new bootstrap.Modal(paymentModalEl.value);
    }
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
