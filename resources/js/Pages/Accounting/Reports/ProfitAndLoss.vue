<template>
    <AdminLayout title="Profit & Loss">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line me-2"></i>Profit &amp; Loss</h3>
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
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">Income</h4>
                        <span class="badge bg-success fs-6">₹ {{ Number(report?.income_total || 0).toFixed(2) }}</span>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Amount ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in report?.income || []" :key="r.account_id">
                                    <td>
                                        <Link :href="`/accounting/reports/ledger?account_id=${r.account_id}`" class="text-decoration-none">
                                            {{ r.account_name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">{{ Number(r.amount).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!(report?.income || []).length">
                                    <td colspan="2" class="text-center text-muted py-4">No income accounts.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">Expenses</h4>
                        <span class="badge bg-danger fs-6">₹ {{ Number(report?.expense_total || 0).toFixed(2) }}</span>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Amount ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in report?.expenses || []" :key="r.account_id">
                                    <td>
                                        <Link :href="`/accounting/reports/ledger?account_id=${r.account_id}`" class="text-decoration-none">
                                            {{ r.account_name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">{{ Number(r.amount).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!(report?.expenses || []).length">
                                    <td colspan="2" class="text-center text-muted py-4">No expense accounts.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <strong>Net Profit / Loss</strong>
                        <span class="badge fs-5" :class="Number(report?.net || 0) >= 0 ? 'bg-success' : 'bg-danger'">
                            ₹ {{ Number(report?.net || 0).toFixed(2) }}
                        </span>
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
    form.get('/accounting/reports/profit-and-loss', { preserveState: true });
}
</script>
