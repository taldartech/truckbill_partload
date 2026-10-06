<template>
    <AdminLayout title="TDS Report">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-hand-holding-usd me-2"></i>TDS Report</h3>
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

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">TDS by Party / Agent</h4>
                        <div class="d-flex gap-2">
                            <span class="badge bg-info fs-6">Receivable: ₹ {{ Number(report?.totals?.receivable || 0).toFixed(2) }}</span>
                            <span class="badge bg-warning text-dark fs-6">Payable: ₹ {{ Number(report?.totals?.payable || 0).toFixed(2) }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered table-hover text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Party / Agent</th>
                                    <th class="text-end">Vouchers</th>
                                    <th class="text-end">TDS Receivable ₹</th>
                                    <th class="text-end">TDS Payable ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in report?.rows || []" :key="row.account_id ?? 'unallocated'">
                                    <td>
                                        <Link
                                            v-if="row.account_id"
                                            :href="`/accounting/reports/party-ledger?account_id=${row.account_id}&start_date=${form.start_date}&end_date=${form.end_date}`"
                                            class="text-decoration-none"
                                        >{{ row.account_name }}</Link>
                                        <span v-else class="text-muted">{{ row.account_name }}</span>
                                    </td>
                                    <td class="text-end">{{ row.vouchers }}</td>
                                    <td class="text-end">{{ Number(row.receivable) !== 0 ? Number(row.receivable).toFixed(2) : '-' }}</td>
                                    <td class="text-end">{{ Number(row.payable) !== 0 ? Number(row.payable).toFixed(2) : '-' }}</td>
                                </tr>
                                <tr v-if="!(report?.rows || []).length">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No TDS entries in the selected period.
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

const props = defineProps({ branches: { type: Array, default: null }, report: Object, filters: Object });

const form = useForm({
    start_date: props.filters?.start_date,
    end_date: props.filters?.end_date,
    branch_id: props.filters?.branch_id || '',
});

function submit() {
    form.get('/accounting/reports/tds', { preserveState: true });
}
</script>
