<template>
    <AdminLayout title="Delivery Receipts">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="m-0 text-dark">Delivery Receipts</h4>
                <Link href="/delivery-receipts/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Delivery Receipt
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
                                <td>{{ receipt.delivery_receipt_no || '--' }}</td>
                                <td>{{ receipt.date }}</td>
                                <td>{{ receipt.gr_no_bilty_no }}</td>
                                <td>{{ receipt.consignee?.name }}</td>
                                <td>{{ receipt.mode_of_payment }}</td>
                                <td class="fw-bold">₹ {{ receipt.final_amount }}</td>
                                <td class="text-end">
                                    <a :href="`/delivery-receipts/${receipt.id}/print`" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Print Receipt">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <Link :href="`/delivery-receipts/${receipt.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </Link>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteReceipt(receipt.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="receipts.data.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">No delivery receipts found.</td>
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
    receipts: Object
});

const deleteForm = useForm({});

const deleteReceipt = (id) => {
    if (confirm('Are you sure you want to delete this delivery receipt?')) {
        deleteForm.delete(`/delivery-receipts/${id}`);
    }
};
</script>
