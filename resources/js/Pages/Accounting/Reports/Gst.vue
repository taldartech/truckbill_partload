<template>
    <AdminLayout title="GST Report">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-percent me-2"></i>GST Report</h3>
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

        <div class="row mb-3">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="m-0 fw-bold">Summary</h4>
                        <div class="d-flex gap-2">
                            <span class="badge bg-success fs-6">Output: ₹ {{ Number(report?.totals?.output || 0).toFixed(2) }}</span>
                            <span class="badge bg-info fs-6">Input: ₹ {{ Number(report?.totals?.input || 0).toFixed(2) }}</span>
                            <span class="badge bg-primary fs-6">Net payable: ₹ {{ Number(report?.totals?.net_payable || 0).toFixed(2) }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered text-sm m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Ledger</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ledger in report?.ledgers || []" :key="ledger.account_id">
                                    <td>{{ ledger.account_name }}</td>
                                    <td class="text-capitalize">{{ ledger.kind }}</td>
                                    <td class="text-end fw-semibold">{{ Number(ledger.amount).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!(report?.ledgers || []).length">
                                    <td colspan="3" class="text-center py-4 text-muted">No GST posted in the selected period.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="(report?.rows || []).length">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="m-0 fw-bold">Source Documents</h4>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered table-hover text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                    <th>Document</th>
                                    <th>Ledger</th>
                                    <th class="text-end">Amount ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in report.rows" :key="index">
                                    <td>{{ row.date }}</td>
                                    <td>
                                        <Link :href="`/accounting/vouchers/${row.voucher_id}`" class="text-decoration-none">{{ row.voucher_number }}</Link>
                                    </td>
                                    <td>
                                        <template v-if="row.source">
                                            {{ row.source.module }}
                                            <Link v-if="row.source.url" :href="row.source.url" class="text-decoration-none">{{ row.source.number }}</Link>
                                            <span v-else>{{ row.source.number }}</span>
                                        </template>
                                        <span v-else class="text-muted">{{ row.narration || 'Manual' }}</span>
                                    </td>
                                    <td>{{ row.account_name }}</td>
                                    <td class="text-end">{{ Number(row.amount).toFixed(2) }}</td>
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
    form.get('/accounting/reports/gst', { preserveState: true });
}
</script>
