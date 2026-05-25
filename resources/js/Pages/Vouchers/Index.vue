<template>
    <AdminLayout title="Vouchers">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="m-0 text-dark">Vouchers</h4>
                <Link href="/vouchers/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Voucher
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
                                <th>Date</th>
                                <th>Voucher No.</th>
                                <th>Type</th>
                                <th>Account Head</th>
                                <th>Dr/Cr</th>
                                <th>Amount</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="voucher in vouchers.data" :key="voucher.id">
                                <td>{{ voucher.id }}</td>
                                <td>{{ voucher.date }}</td>
                                <td>{{ voucher.voucher_number || '--' }}</td>
                                <td>
                                    <span class="badge" :class="{
                                        'bg-success': voucher.voucher_type === 'Receipt',
                                        'bg-danger': voucher.voucher_type === 'Payment',
                                        'bg-info': voucher.voucher_type === 'Journal',
                                        'bg-secondary': voucher.voucher_type === 'Contra'
                                    }">{{ voucher.voucher_type }}</span>
                                </td>
                                <td>{{ voucher.account_head }}</td>
                                <td :class="voucher.debit_credit === 'Dr' ? 'text-danger fw-bold' : 'text-success fw-bold'">{{ voucher.debit_credit }}</td>
                                <td>₹ {{ voucher.amount }}</td>
                                <td class="text-end">
                                    <Link :href="`/vouchers/${voucher.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </Link>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteVoucher(voucher.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="vouchers.data.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">No vouchers found.</td>
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
    vouchers: Object
});

const deleteForm = useForm({});

const deleteVoucher = (id) => {
    if (confirm('Are you sure you want to delete this voucher?')) {
        deleteForm.delete(`/vouchers/${id}`);
    }
};
</script>
