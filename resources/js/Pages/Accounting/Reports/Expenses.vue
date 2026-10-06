<template>
    <AdminLayout title="Expense Report">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-receipt me-2"></i>Expense Report</h3>
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
                        <h4 class="m-0 fw-bold">Expenses by Ledger</h4>
                        <span class="badge bg-danger fs-6">Total: ₹ {{ Number(report?.total || 0).toFixed(2) }}</span>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Ledger</th>
                                    <th v-for="month in report?.months || []" :key="month" class="text-end">{{ monthLabel(month) }}</th>
                                    <th class="text-end">Total ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="group in report?.groups || []" :key="group.group_name">
                                    <tr class="table-light fw-bold">
                                        <td :colspan="(report.months.length || 0) + 1">{{ group.group_name }}</td>
                                        <td class="text-end">{{ Number(group.total).toFixed(2) }}</td>
                                    </tr>
                                    <tr v-for="ledger in group.ledgers" :key="ledger.account_id">
                                        <td class="ps-4">
                                            <Link :href="`/accounting/reports/ledger?account_id=${ledger.account_id}&start_date=${form.start_date}&end_date=${form.end_date}`" class="text-decoration-none">
                                                {{ ledger.account_name }}
                                            </Link>
                                        </td>
                                        <td v-for="month in report.months" :key="month" class="text-end">
                                            {{ ledger.months[month] ? Number(ledger.months[month]).toFixed(2) : '-' }}
                                        </td>
                                        <td class="text-end fw-semibold">{{ Number(ledger.total).toFixed(2) }}</td>
                                    </tr>
                                </template>
                                <tr v-if="!(report?.groups || []).length">
                                    <td :colspan="(report?.months?.length || 0) + 2" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No expenses posted in the selected period.
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

const monthLabel = (month) => {
    const [year, m] = month.split('-');
    return new Date(Number(year), Number(m) - 1, 1).toLocaleString('en-IN', { month: 'short', year: '2-digit' });
};

function submit() {
    form.get('/accounting/reports/expenses', { preserveState: true });
}
</script>
