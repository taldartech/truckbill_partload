<template>
    <AdminLayout title="Day Book">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-day me-2"></i>Day Book</h3>
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
                    <div class="card-header">
                        <h4 class="m-0 fw-bold">Day Book Entries</h4>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-bordered text-sm m-0">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                    <th>Type</th>
                                    <th>Narration</th>
                                    <th class="text-end">Amount ₹</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.id">
                                    <td>{{ row.date }}</td>
                                    <td>
                                        <Link :href="`/accounting/vouchers/${row.id}`" class="text-decoration-none fw-semibold">
                                            {{ row.voucher_number }}
                                        </Link>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ row.voucher_type_label }}</span></td>
                                    <td>{{ row.narration || '—' }}</td>
                                    <td class="text-end fw-bold">{{ Number(row.debit).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!rows?.length">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        No vouchers found for the selected date range.
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

const props = defineProps({ branches: { type: Array, default: null }, rows: Array, filters: Object });
const form = useForm({ branch_id: props.filters?.branch_id || '',
    start_date: props.filters?.start_date,
    end_date: props.filters?.end_date,
});
function submit() {
    form.get('/accounting/reports/day-book', { preserveState: true });
}
</script>
