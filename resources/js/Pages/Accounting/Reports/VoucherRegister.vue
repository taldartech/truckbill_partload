<template>
    <AdminLayout title="Voucher Register">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list-ol me-2"></i>Journal / Voucher Register</h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit" class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Voucher Type</label>
                                <select v-model="form.voucher_type" class="form-select form-select-sm">
                                    <option value="">All types</option>
                                    <option v-for="t in voucherTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.start_date">
                            </div>
                            <div class="col-md-2">
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
                        <h4 class="m-0 fw-bold">Vouchers ({{ rows.length }})</h4>
                        <span class="badge bg-primary fs-6">Total: ₹ {{ total.toFixed(2) }}</span>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                    <th>Origin</th>
                                    <th>Account</th>
                                    <th class="text-end">Debit ₹</th>
                                    <th class="text-end">Credit ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="row in rows" :key="row.id">
                                    <tr v-for="(entry, index) in row.entries" :key="`${row.id}-${index}`">
                                        <td v-if="index === 0" :rowspan="row.entries.length" class="align-top">{{ row.date }}</td>
                                        <td v-if="index === 0" :rowspan="row.entries.length" class="align-top">
                                            <Link :href="`/accounting/vouchers/${row.id}`" class="text-decoration-none fw-semibold">
                                                {{ row.voucher_number }}
                                            </Link>
                                            <div><span class="badge bg-secondary">{{ row.voucher_type_label }}</span>
                                                <span v-if="row.is_reversal" class="badge bg-warning text-dark ms-1">Reversal</span></div>
                                            <div class="small text-muted">{{ row.narration }}</div>
                                        </td>
                                        <td v-if="index === 0" :rowspan="row.entries.length" class="align-top small">
                                            <template v-if="row.source">
                                                {{ row.source.module }}
                                                <Link v-if="row.source.url" :href="row.source.url" class="text-decoration-none">{{ row.source.number }}</Link>
                                                <span v-else>{{ row.source.number }}</span>
                                            </template>
                                            <template v-else>{{ row.event_label || 'Manual' }}</template>
                                        </td>
                                        <td>{{ entry.account_name }}</td>
                                        <td class="text-end text-danger">{{ entry.debit > 0 ? Number(entry.debit).toFixed(2) : '-' }}</td>
                                        <td class="text-end text-success">{{ entry.credit > 0 ? Number(entry.credit).toFixed(2) : '-' }}</td>
                                    </tr>
                                </template>
                                <tr v-if="!rows.length">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No vouchers found for the selected filters.
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
import { computed } from 'vue';

const props = defineProps({
    branches: { type: Array, default: null },
    rows: { type: Array, default: () => [] },
    voucherTypes: { type: Array, default: () => [] },
    filters: Object,
});

const form = useForm({
    voucher_type: props.filters?.voucher_type || '',
    start_date: props.filters?.start_date,
    end_date: props.filters?.end_date,
    branch_id: props.filters?.branch_id || '',
});

const total = computed(() => props.rows.reduce((sum, row) => sum + Number(row.debit || 0), 0));

function submit() {
    form.get('/accounting/reports/voucher-register', { preserveState: true });
}
</script>
