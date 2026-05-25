<template>
    <AdminLayout title="Invoices">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="m-0 text-dark">Invoices</h4>
                <Link href="/invoices/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create Invoice
                </Link>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped m-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th># ID</th>
                                <th>Invoice No.</th>
                                <th>Date</th>
                                <th>Party Name</th>
                                <th>Total Amount</th>
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
                                <td class="fw-bold">₹ {{ invoice.net_amount }}</td>
                                <td>
                                    <span class="badge" :class="invoice.status === 'Paid' ? 'bg-success' : 'bg-danger'">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a :href="`/invoices/${invoice.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Print Invoice">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <Link :href="`/invoices/${invoice.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </Link>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteInvoice(invoice.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="invoices.data.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">No invoices found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    invoices: Object
});

const deleteForm = useForm({});

const deleteInvoice = (id) => {
    if (confirm('Are you sure you want to delete this invoice?')) {
        deleteForm.delete(`/invoices/${id}`);
    }
};
</script>
