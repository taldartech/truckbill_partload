<template>
    <AdminLayout title="Delivery Receipt">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Delivery Receipts</h3>
                        <Link v-if="can('create delivery_receipts')" href="/delivery-receipts/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Delivery Receipt
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Receipt No.</th>
                                    <th>Date</th>
                                    <th>GR/Bilty No.</th>
                                    <th>Consignee</th>
                                    <th>Mode of Payment</th>
                                    <th>Final Amount</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="receipt in receipts.data" :key="receipt.id">
                                    <td>{{ receipt.id }}</td>
                                    <td>{{ receipt.delivery_receipt_no || '—' }}</td>
                                    <td>{{ receipt.date }}</td>
                                    <td>{{ receipt.gr_no_bilty_no }}</td>
                                    <td>{{ receipt.consignee?.name }}</td>
                                    <td>{{ receipt.mode_of_payment }}</td>
                                    <td class="fw-bold">₹ {{ receipt.final_amount }}</td>
                                    <td class="text-end">
                                        <a :href="`/delivery-receipts/${receipt.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-2" title="Print Receipt">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <Link :href="`/delivery-receipts/${receipt.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button class="btn btn-sm btn-danger" @click="deleteReceipt(receipt.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="receipts.data.length === 0">
                                    <td colspan="8" class="text-center py-4">No delivery receipts found.</td>
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
    receipts: Object,
});

const deleteForm = useForm({});

const deleteReceipt = (id) => {
    if (confirm('Are you sure you want to delete this delivery receipt?')) {
        deleteForm.delete(`/delivery-receipts/${id}`);
    }
};
</script>
