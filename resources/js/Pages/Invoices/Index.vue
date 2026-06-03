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
                                        <a :href="`/invoices/${invoice.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-2" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/invoices/${invoice.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button class="btn btn-sm btn-danger" @click="deleteInvoice(invoice.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="7" class="text-center py-4">No invoices found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    invoices: Object,
});

const deleteForm = useForm({});

const deleteInvoice = (id) => {
    if (confirm('Are you sure you want to delete this invoice?')) {
        deleteForm.delete(`/invoices/${id}`);
    }
};
</script>
