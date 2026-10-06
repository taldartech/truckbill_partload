<template>
    <AdminLayout title="Balance Sheet">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-invoice-dollar me-2"></i>Balance Sheet</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">As On</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.as_on">
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
                        <h4 class="m-0 fw-bold">Assets</h4>
                        <span class="badge bg-primary fs-6">₹ {{ Number(report?.totals?.assets || 0).toFixed(2) }}</span>
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
                                <tr v-for="r in report?.assets || []" :key="r.account_id">
                                    <td>
                                        <Link :href="`/accounting/reports/ledger?account_id=${r.account_id}`" class="text-decoration-none">
                                            {{ r.account_name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">{{ Number(r.amount).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!(report?.assets || []).length">
                                    <td colspan="2" class="text-center text-muted py-4">No asset balances.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">Liabilities &amp; Capital</h4>
                        <span class="badge bg-primary fs-6">₹ {{ Number(report?.totals?.liabilities_capital || 0).toFixed(2) }}</span>
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
                                <tr class="table-light"><td colspan="2" class="fw-bold small text-uppercase">Liabilities</td></tr>
                                <tr v-for="r in report?.liabilities || []" :key="'l'+r.account_id">
                                    <td>
                                        <Link :href="`/accounting/reports/ledger?account_id=${r.account_id}`" class="text-decoration-none">
                                            {{ r.account_name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">{{ Number(r.amount).toFixed(2) }}</td>
                                </tr>
                                <tr class="table-light"><td colspan="2" class="fw-bold small text-uppercase">Capital</td></tr>
                                <tr v-for="r in report?.capital || []" :key="'c'+r.account_id">
                                    <td>
                                        <Link :href="`/accounting/reports/ledger?account_id=${r.account_id}`" class="text-decoration-none">
                                            {{ r.account_name }}
                                        </Link>
                                    </td>
                                    <td class="text-end">{{ Number(r.amount).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Net Profit / Loss</td>
                                    <td class="text-end fw-bold">{{ Number(report?.net_profit || 0).toFixed(2) }}</td>
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
const form = useForm({ branch_id: props.filters?.branch_id || '', as_on: props.filters?.as_on });
function submit() {
    form.get('/accounting/reports/balance-sheet', { preserveState: true });
}
</script>
