<template>
    <AdminLayout title="Voucher">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Manage Vouchers</h3>
                        <Link v-if="can('create vouchers')" href="/vouchers/create" class="btn btn-primary btn-sm ms-auto">
                            <i class="fas fa-plus"></i> Add New Voucher
                        </Link>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
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
                                    <td>{{ voucher.voucher_number || '—' }}</td>
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
                                        <Link :href="`/vouchers/${voucher.id}/edit`" class="btn btn-sm btn-info text-white me-2">
                                            <i class="fas fa-edit"></i>
                                        </Link>
                                        <button class="btn btn-sm btn-danger" @click="deleteVoucher(voucher.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="vouchers.data.length === 0">
                                    <td colspan="8" class="text-center py-4">No vouchers found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :pagination="vouchers" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';

const page = usePage();
const can = (permission) => {
    return page.props.auth.is_admin || page.props.auth.permissions.includes(permission);
};

defineProps({
    vouchers: Object,
});

const deleteForm = useForm({});

const deleteVoucher = (id) => {
    if (confirm('Are you sure you want to delete this voucher?')) {
        deleteForm.delete(`/vouchers/${id}`);
    }
};
</script>
