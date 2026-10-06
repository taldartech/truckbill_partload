<template>
    <AdminLayout title="Cash Book">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill-wave me-2"></i>Cash Book</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.start_date">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.end_date">
                            </div>
                            <ReportBranchFilter v-model="form.branch_id" :branches="branches" />
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success btn-sm w-100">Refresh</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="ledger">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">{{ account?.name || 'Cash' }} - Cash Book</h4>
                        <div class="d-flex gap-2">
                            <span class="badge bg-secondary fs-6">Opening: ₹ {{ Number(ledger.opening).toFixed(2) }}</span>
                            <span class="badge bg-primary fs-6">Closing: ₹ {{ Number(ledger.closing).toFixed(2) }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Voucher</th>
                                    <th class="text-end">Debit (Dr) ₹</th>
                                    <th class="text-end">Credit (Cr) ₹</th>
                                    <th class="text-end">Balance ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in ledger.rows" :key="i">
                                    <td>{{ row.date }}</td>
                                    <td>{{ row.particulars || '—' }}</td>
                                    <td>
                                        <Link :href="`/accounting/vouchers/${row.voucher_id}`" class="text-decoration-none">
                                            {{ row.voucher_number }}
                                        </Link>
                                    </td>
                                    <td class="text-end text-danger">{{ Number(row.debit) > 0 ? Number(row.debit).toFixed(2) : '-' }}</td>
                                    <td class="text-end text-success">{{ Number(row.credit) > 0 ? Number(row.credit).toFixed(2) : '-' }}</td>
                                    <td class="text-end fw-bold">{{ Number(row.balance).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!ledger.rows?.length">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No cash transactions found for the selected date range.
                                    </td>
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
import ReportBranchFilter from '@/Components/Accounting/ReportBranchFilter.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ branches: { type: Array, default: null }, account: Object, ledger: Object, filters: Object });
const form = useForm({ branch_id: props.filters?.branch_id || '',
    start_date: props.filters?.start_date,
    end_date: props.filters?.end_date,
});
function submit() {
    form.get('/accounting/reports/cash-book', { preserveState: true });
}
</script>
