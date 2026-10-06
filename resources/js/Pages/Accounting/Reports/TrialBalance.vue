<template>
    <AdminLayout title="Trial Balance">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-balance-scale me-2"></i>Trial Balance</h3>
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
                        <h4 class="m-0 fw-bold">Trial Balance</h4>
                        <div class="d-flex gap-2">
                            <span class="badge bg-danger fs-6">Dr: ₹ {{ Number(report?.totals?.debit || 0).toFixed(2) }}</span>
                            <span class="badge bg-success fs-6">Cr: ₹ {{ Number(report?.totals?.credit || 0).toFixed(2) }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Account</th>
                                    <th>Group</th>
                                    <th class="text-end">Debit (Dr) ₹</th>
                                    <th class="text-end">Credit (Cr) ₹</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in report?.rows || []" :key="row.account_id">
                                    <td class="fw-semibold">{{ row.account_name }}</td>
                                    <td>{{ row.group_name }}</td>
                                    <td class="text-end text-danger">{{ Number(row.closing_debit) > 0 ? Number(row.closing_debit).toFixed(2) : '-' }}</td>
                                    <td class="text-end text-success">{{ Number(row.closing_credit) > 0 ? Number(row.closing_credit).toFixed(2) : '-' }}</td>
                                    <td class="text-end">
                                        <Link :href="`/accounting/reports/ledger?account_id=${row.account_id}`" class="btn btn-sm btn-outline-primary">
                                            Ledger
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!(report?.rows || []).length">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No balances for the selected period.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="(report?.rows || []).length">
                                <tr class="fw-bold table-light">
                                    <td colspan="2">Total</td>
                                    <td class="text-end">{{ Number(report?.totals?.debit || 0).toFixed(2) }}</td>
                                    <td class="text-end">{{ Number(report?.totals?.credit || 0).toFixed(2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3" v-if="(report?.groups || []).length">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="m-0 fw-bold">Group Totals</h4>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered text-sm m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Group</th>
                                    <th>Nature</th>
                                    <th class="text-end">Period Dr ₹</th>
                                    <th class="text-end">Period Cr ₹</th>
                                    <th class="text-end">Closing Dr ₹</th>
                                    <th class="text-end">Closing Cr ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="group in report.groups" :key="group.group_id">
                                    <td class="fw-semibold">{{ group.group_name }}</td>
                                    <td class="text-capitalize">{{ group.nature }}</td>
                                    <td class="text-end">{{ Number(group.debit).toFixed(2) }}</td>
                                    <td class="text-end">{{ Number(group.credit).toFixed(2) }}</td>
                                    <td class="text-end text-danger">{{ Number(group.closing_debit) > 0 ? Number(group.closing_debit).toFixed(2) : '-' }}</td>
                                    <td class="text-end text-success">{{ Number(group.closing_credit) > 0 ? Number(group.closing_credit).toFixed(2) : '-' }}</td>
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
const form = useForm({ branch_id: props.filters?.branch_id || '',
    start_date: props.filters?.start_date,
    end_date: props.filters?.end_date,
});
function submit() {
    form.get('/accounting/reports/trial-balance', { preserveState: true });
}
</script>
